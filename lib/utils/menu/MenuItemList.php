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

    /**
     * Select active MenuItem by matching the current page URL with the MenuItem url
     * Matches by full url, then scriptName only, then scriptPath only
     * Constructs a selection path array containing MenuItems - from top parent MenuItem to selected MenuItem
     * @throws Exception
     */
    const string MATCH_FULL = "MATCH_FULL";
    const string MATCH_PARTIAL = "MATCH_PARTIAL";
    const string MATCH_SCRIPT = "MATCH_SCRIPT";
    const string MATCH_PATH = "MATCH_PATH";
    const array DEFAULT_SELECT_MATCHERS = array(MenuItemList::MATCH_FULL,MenuItemList::MATCH_PARTIAL,MenuItemList::MATCH_SCRIPT,MenuItemList::MATCH_PATH);

    public function selectActive(array $matchers = MenuItemList::DEFAULT_SELECT_MATCHERS, bool $match_first=true) : ?MenuItem
    {

        Debug::ErrorLog("Select matchers: ", $matchers);

        $pageURL = URL::Current();

        Debug::ErrorLog("Current URL: " . $pageURL->toString());

        $match_code = array();

        $match_full = function (MenuItem $item, URL $itemURL) use ($pageURL) {
            $match = (strcmp(mb_strtolower($itemURL->toString()), mb_strtolower($pageURL->toString())) == 0);
            if ($match) {
                Debug::ErrorLog("Match full URL: " . $itemURL->toString() . " - matches");
            }
            return $match;
        };
        $match_code[MenuItemList::MATCH_FULL] = $match_full;

        $match_partial = function (MenuItem $item, URL $itemURL) use ($pageURL) {
            $match = (str_starts_with(mb_strtolower($pageURL->toString()), mb_strtolower($itemURL->toString())));
            if ($match) {
                Debug::ErrorLog("Match partial URL: " . $itemURL->toString() . " - matches");
            }
            return $match;
        };
        $match_code[MenuItemList::MATCH_PARTIAL] = $match_partial;

        $match_script = function (MenuItem $item, URL $itemURL) use ($pageURL) {
            $match = (strcmp(mb_strtolower($itemURL->getScriptName()), mb_strtolower($pageURL->getScriptName())) == 0);
            if ($match) {
                Debug::ErrorLog("Match scriptName: " . $itemURL->getScriptName() . " - matches");
            }
            return $match;
        };
        $match_code[MenuItemList::MATCH_SCRIPT] = $match_script;

        $match_path = function (MenuItem $item, URL $itemURL) use ($pageURL) {
            $match = (strcmp(mb_strtolower($itemURL->getScriptPath()), mb_strtolower($pageURL->getScriptPath())) == 0);
            if ($match) {
                Debug::ErrorLog("Match scriptPath: " . $itemURL->getScriptPath() . " - matches");
            }
            return $match;
        };
        $match_code[MenuItemList::MATCH_PATH] = $match_path;

        $this->deselect();

        $selected_item = null;

        foreach ($matchers as $idx=>$mode) {
            $code = $match_code[$mode];

            Debug::ErrorLog("Using: ".$mode);

            $match_item = $this->matchItems($code);

            if ($match_item instanceof MenuItem) {
                $selected_item = $match_item;
                //keep matching
                if ($match_first) {
                    break;
                }
            }
        }

        if ($selected_item instanceof MenuItem) {
            $selected_item->setSelected(true);
        }

        return $selected_item;
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



    /**
     * Call match method of each MenuItem element in this list
     * Return the first MenuItem whose match method return true using the closure call
     *
     * @param Closure $matcher Closure function to execute during matching
     * @return MenuItem|null The matching MenuItem or NULL if none of the elements match
     * @throws Exception Throws exception if element from items is not instance of MenuItem
     */
    protected function matchItems(Closure $matcher) : ?MenuItem
    {
        $result = null;

        $iterator = $this->iterator();
        while ($item = $iterator->next()){
            if (! ($item instanceof MenuItem)) continue;
            $result = $item->matchItems($matcher);
            if ($result instanceof MenuItem) break;
        }

        return $result;
    }



}