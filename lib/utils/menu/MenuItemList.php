<?php
include_once("objects/SparkList.php");
include_once("utils/menu/MenuItem.php");

class MenuItemList extends SparkList
{

    public function __construct(?array $main_menu = array())
    {
        parent::__construct();

        foreach ($main_menu as $menu_item) {
            if ($menu_item instanceof MenuItem) {
                $this->append($menu_item);
            }
        }
    }

    protected function matchPath(int &$idx, array $matchPath, array &$matched) : void
    {
        if ($idx > count($matchPath)-1) return;

        //Debug::ErrorLog("Start Matching: IDX=$idx PathSegment: $matchPath[$idx] : ItemListCount: {$this->count()}");

        $found = null;

        $iterator = $this->iterator();
        while ($item = $iterator->next()) {
            if (!($item instanceof MenuItem)) continue;

            if (strcasecmp($matchPath[$idx], $item->getHref()) === 0) {
                $found = $item;
                break;
            }
        }

        if ($found instanceof MenuItem) {
            $matched[] = $item;
            $idx++;
            $found->matchPath($idx, $matchPath, $matched);
        }

    }

    public function removePath(string $path) : void
    {
        Debug::ErrorLog("Removing path: $path");
        //selectPath() returns the matched chain (parents first) - remove only the item the path points to
        $result = $this->selectPath($path, false);
        $item = end($result);
        if (!($item instanceof MenuItem)) return;

        //the whole path must match - a partial match points to a parent
        if (count($result) != count(Spark::Split($path))) return;

        $parentItem = $item->getParent();
        if ($parentItem instanceof MenuItem) {
            $parentItem->removeObject($item);
        }
        else {
            //top level item
            $this->removeObject($item);
        }
    }

    public function selectPath(string $path, bool $setSelected = true) : array
    {
        if ($setSelected) {
            $this->deselect();
        }

        $matchPath = Spark::Split($path);

        $idx = 0;
        $matched = array();

        $this->matchPath($idx, $matchPath, $matched);

        if ($setSelected) {
            if (count($matched) > 0) {
                $last = end($matched);
                if ($last instanceof MenuItem) {
                    $last->setSelected(true, true);
                }
            }
        }
        return $matched;
    }
    /**
     * Select the item whose link points to the page, with its parents.
     *
     * An item matches an address when its path is the address path or a folder of it (whole segments) and its
     * query parameters are all in the address with the same values - the 'Books' item /products/category/110/books/
     * stays selected on /products/category/110/books/?page=2. The home link '/' matches the home only.
     * 'index.php' at the end of a path is its folder; letter case and url encoding are not compared.
     * Links to other hosts, '#' and 'javascript:' links do not match.
     *
     * The best match: the longest path, then more parameters, then the earlier address, then the deeper item.
     *
     * @param URL|string ...$addresses the addresses of the page, the preferred first - default URL::Requested().
     * A page can add the addresses of its place, ex. the category of a product.
     * @return MenuItem|null the selected item
     */
    public function selectActive(URL|string ...$addresses) : ?MenuItem
    {
        if (count($addresses) == 0) {
            $addresses[] = URL::Requested();
        }

        $pages = array();
        foreach ($addresses as $address) {
            $page = MenuItemList::LinkParts((string)$address);
            if (!is_null($page)) $pages[] = $page;
        }

        $this->deselect();

        $best = null;
        $bestRank = null;
        $this->forEachItem(function (MenuItem $item, int $depth) use ($pages, &$best, &$bestRank) {
            $link = MenuItemList::LinkParts($item->getHref());
            if (is_null($link)) return;
            foreach ($pages as $idx => $page) {
                $score = MenuItemList::LinkScore($link, $page);
                if ($score < 0) continue;
                //compared in order: score, the earlier address, the deeper item
                $rank = array($score, -$idx, $depth);
                if (is_null($bestRank) || $rank > $bestRank) {
                    $best = $item;
                    $bestRank = $rank;
                }
            }
        });

        if ($best instanceof MenuItem) {
            Debug::ErrorLog("Selected menu item: " . $best->getName() . " - " . $best->getHref());
            $best->setSelected(true);
        }

        return $best;
    }

    /**
     * Calls $visit(MenuItem $item, int $depth) for every item - each item before its sub items. Depth 0 is the top.
     */
    public function forEachItem(Closure $visit, int $depth = 0) : void
    {
        $iterator = $this->iterator();
        while ($item = $iterator->next()) {
            if (!($item instanceof MenuItem)) continue;
            $visit($item, $depth);
            $item->forEachItem($visit, $depth + 1);
        }
    }

    /**
     * The path segments and query parameters of a link to this site - null for links to other hosts, '#...',
     * 'javascript:', 'mailto:' and the like
     * @return array|null [segments, parameters]
     */
    public static function LinkParts(string $link) : ?array
    {
        $link = trim($link);
        if ($link === "" || str_starts_with($link, "#")) return null;

        $parts = parse_url($link);
        if ($parts === false) return null;
        if (isset($parts["scheme"]) && !in_array(strtolower($parts["scheme"]), array("http", "https"))) return null;
        if (isset($parts["host"])) {
            $host = strtolower((string)($_SERVER["HTTP_HOST"] ?? ""));
            $host = preg_replace("/:\\d+$/", "", $host);
            if (strcasecmp($parts["host"], $host) !== 0) return null;
        }

        $path = mb_strtolower(rawurldecode($parts["path"] ?? "/"));
        $path = preg_replace("#(^|/)index\\.php$#", "/", $path);
        $segments = array_values(array_filter(explode("/", $path), fn(string $segment) => $segment !== ""));

        $parameters = array();
        if (isset($parts["query"])) {
            parse_str($parts["query"], $parameters);
        }
        return array($segments, $parameters);
    }

    /**
     * How well a link matches a page address (parts of LinkParts) - -1 for no match
     */
    public static function LinkScore(array $link, array $page) : int
    {
        [$linkSegments, $linkParameters] = $link;
        [$pageSegments, $pageParameters] = $page;

        //the home link matches the home only
        if (count($linkSegments) == 0 && count($pageSegments) > 0) return -1;

        if (count($linkSegments) > count($pageSegments)) return -1;
        if (array_slice($pageSegments, 0, count($linkSegments)) !== $linkSegments) return -1;

        foreach ($linkParameters as $name => $value) {
            if (!array_key_exists($name, $pageParameters)) return -1;
            if (mb_strtolower(json_encode($value)) !== mb_strtolower(json_encode($pageParameters[$name]))) return -1;
        }

        return count($linkSegments) * 100 + count($linkParameters);
    }

    /**
     * Get the deepest branch end node that has selected state 'true'
     * @return MenuItem|null
     */
    protected function getSelected() :?MenuItem
    {
        $result = null;

        $iterator = $this->iterator();
        while ($item = $iterator->next()) {
            if (!($item instanceof MenuItem)) continue;

            //find deepest selected node
            if ($item->isSelected()) {

                $result = $item->getSelected();
                if ($result instanceof MenuItem) break;

                $result = $item;
                break;
            }

        }

        return $result;
    }

    /**
     * Returns all selected items from top parent to end node
     * @return array
     */
    public function getSelectedPath(): array
    {

        $result = array();

        $current = $this->getSelected();

        if (!($current instanceof MenuItem)) return $result;

        $result[] = $current;

        while ($parent = $current->getParent()) {
            $result[] = $parent;
            $current = $parent;
        }

        return array_reverse($result);
    }

    public function deselect() : void
    {
        $selected = $this->getSelected();
        if ($selected instanceof MenuItem) {
            $selected->setSelected(false);
        }

    }






}