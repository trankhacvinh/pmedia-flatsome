<?php
if (!defined('ABSPATH')) { exit; }

interface PMFAI_AI_Provider_Interface
{
    public function complete(array $request);
}
