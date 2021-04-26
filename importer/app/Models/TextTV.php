<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TextTV extends Model
{
    use HasFactory;

    protected $table = 'texttv';
    //public $timestamps = false;
    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'date_updated';
    protected $fillable = [
        'date_added',
        'date_updated',
        'page_num',
        'title',
        'page_content',
        'next_page',
        'prev_page',
        'is_shared'
    ];

    public function pageContentUncompressed()
    {
        try {
            $uncompressedPageContent = gzuncompress(substr($this->page_content, 4));
            $unserializedPageContent = unserialize($uncompressedPageContent);
        } catch (\Exception $e) {
            $unserializedPageContent = [];
        }

        return $unserializedPageContent;
    }
}
