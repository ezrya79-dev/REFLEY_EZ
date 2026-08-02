<?php

namespace App\Enums;

/** Types de zone de contenu éditable — volontairement limités à trois. */
enum ContentType: string
{
    case Text = 'text';
    case Markdown = 'markdown';
    case Image = 'image';
}
