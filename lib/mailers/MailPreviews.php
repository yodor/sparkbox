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
 *
 * A previews.php also tells the addresses its mails use (SetAddress) - the page shows them above the mails: the
 * sender, where the enquiries, the orders ... go. A later layer replaces a key (the store: the enquiries go to the
 * shop's address).
 */
class MailPreviews
{
    /**
     * @var array<string, array{0: string, 1: string, 2: string}> key => array(title, address, where it is set)
     */
    protected static array $addresses = array();

    /**
     * An address the mails of a layer use, for the page: its title, the address (empty - not set) and where it is set
     */
    public static function SetAddress(string $key, string $title, string $address, string $setIn = "") : void
    {
        MailPreviews::$addresses[$key] = array($title, trim($address), $setIn);
    }

    /**
     * The addresses of the layers (after All() - the previews files set them)
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function Addresses() : array
    {
        return MailPreviews::$addresses;
    }

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
