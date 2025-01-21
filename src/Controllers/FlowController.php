<?php

namespace Attla\Authentic\Controllers;

class FlowController extends \Core\Controller {
    protected function feature(?string $feat = null): string
    {
        return __NAMESPACE__ . '\\' ;
    }
}
