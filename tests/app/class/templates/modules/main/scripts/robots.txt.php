<?php
//a script path of the test site: answers on its own, no module page
header("Content-Type: text/plain; charset=UTF-8");
echo "dotted script " . Module::Active()->getName();
