<?php
include_once("beans/OrderedDataBean.php");

class DynamicPagesBean extends OrderedDataBean
{
    protected string $createString = "CREATE TABLE `dynamic_pages` (
  `dpID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `item_title` varchar(255) NOT NULL DEFAULT '',
  `item_date` date DEFAULT '0000-00-00',
  `visible` tinyint(1) DEFAULT 0,
  `subtitle` text DEFAULT NULL,
  `content` text NOT NULL,
  `keywords` text NOT NULL DEFAULT '',
  `position` int(11) unsigned DEFAULT NULL,
  `photo` longblob DEFAULT NULL,
  PRIMARY KEY (`dpID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8
";

    public function __construct()
    {
        parent::__construct("dynamic_pages");
    }

    /**
     * The levels of a page group - 'keywords' are ';' separated levels ('footer_page; terms;' → footer_page, terms).
     * Only plain names (letters, digits, _ and -); others are left out.
     * @param string $keywords
     * @return array<string>
     */
    public static function Levels(string $keywords) : array
    {
        $levels = array();
        foreach (explode(";", $keywords) as $level) {
            $level = trim($level);
            if (preg_match("/^[\\p{L}\\p{N}_-]{1,64}$/u", $level)) $levels[] = $level;
        }
        return $levels;
    }

    /**
     * Visible pages of the group '$levels': their keywords hold every level as a whole level (not a part of a
     * name). Order: newest first ($byPosition: the admin position first - menus).
     * @param array<string> $levels
     * @param bool $byPosition
     * @param string ...$columns
     * @return SelectQuery
     * @throws Exception
     */
    public function groupQuery(array $levels, bool $byPosition, string ...$columns) : SelectQuery
    {
        $query = $this->query($this->key(), ...$columns);
        $query->stmt->where()->match("visible", 1);
        foreach (array_values($levels) as $pos => $level) {
            //';' around the levels - a whole level matches, LIKE wildcards in the name are literal
            $query->stmt->where()->expression("CONCAT(';', REPLACE(keywords, ' ', ''), ';') LIKE :level$pos");
            $query->stmt->where()->bind(":level$pos", "%;" . addcslashes($level, "%_\\") . ";%");
        }
        if ($byPosition) $query->stmt->order("position", OrderDirection::ASC);
        $query->stmt->order("item_date", OrderDirection::DESC);
        $query->stmt->order($this->key(), OrderDirection::DESC);
        return $query;
    }

}