<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Provider extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Markdown description rendered as sanitized HTML.
     */
    protected function descriptionHtml(): Attribute
    {
        return Attribute::make(
            get: function () {
                $description = trim((string) $this->description);

                if ($description === '') {
                    return null;
                }

                return (string) Str::markdown($description, [
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                    'max_nesting_level' => 5,
                ]);
            },
        );
    }
}
