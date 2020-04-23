<?php

//tag to recognize all components that require rendering in the HEAD section of the page
interface IHeadContents
{
    /**
     * @return array All required CSS URLs
     */
    public function requiredStyle();

    /**
     * @return array All required JavaScript URLs
     */
    public function requiredScript();

    /**
     * @return array All required meta tags
     */
    public function requiredMeta();
}

?>
