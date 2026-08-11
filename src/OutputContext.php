<?php

declare(strict_types=1);

namespace Kabuto;

enum OutputContext
{
    case HtmlText;
    case HtmlAttribute;
    case UrlAttribute;
    case Forbidden;
    case Unsupported;
}
