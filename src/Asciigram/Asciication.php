<?php

namespace Asciigram;

use Silex\Application;

class Asciication extends Application
{
    use Application\TwigTrait;
    use Application\FormTrait;
    use Application\UrlGeneratorTrait;
}