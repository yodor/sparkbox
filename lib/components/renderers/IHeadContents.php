<?php

//tag to recognize all components that require rendering in the HEAD section of the page
interface IHeadContents
{
    /**
     * @return mixed All required CSS URLs. or inline e<style></style> string
     */
    public function requiredStyle();

    /**
     * @return array All required JavaScript URLs or inline <script></script> string
     */
    public function requiredScript();

    /**
     * @return array All required meta tags
     */
    public function requiredMeta();
}

?>
