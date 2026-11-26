<?php
include_once("mailers/Mailer.php");

/**
 * The mails of the layers with sample or recent data - the admin Settings > Mails shows and test-sends them.
 *
 *   <layer>/mails/previews.php   returns array(name => array(title, Closure(): Mailer)) - core lib/mails,
 *                                store lib/store/mails, the site class/mails (a later layer replaces a name)
 *
 * A closure builds the mailer only (no send, no database change); the data of a recent row (the last order) or
 * samples.
 */
class MailPreviews
{
    /**
     * @return array<string, array{0: string, 1: Closure}> name => array(title, closure)
     */
    public static function All() : array
    {
        $all = array();
        foreach (SparkLoader::Factory(MailView::FOLDER)->findAll("previews") as $file) {
            $list = include($file);
            if (is_array($list)) $all = array_replace($all, $list);
        }
        return $all;
    }

    /**
     * The mailer of the preview '$name'
     * @throws Exception an unknown name, or the mail can not be built (no order yet)
     */
    public static function Create(string $name) : Mailer
    {
        $all = MailPreviews::All();
        if (!isset($all[$name])) throw new Exception("Unknown mail: $name");
        $mailer = ($all[$name][1])();
        if (!($mailer instanceof Mailer)) throw new Exception("Not a mailer: $name");
        return $mailer;
    }
}
