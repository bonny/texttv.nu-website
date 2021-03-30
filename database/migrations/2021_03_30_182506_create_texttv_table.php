<?php

/*
CREATE TABLE `texttv` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `page_num` smallint(11) DEFAULT NULL,
  `page_content` blob,
  `date_updated` datetime DEFAULT NULL,
  `next_page` int(11) DEFAULT NULL,
  `prev_page` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `is_shared` tinyint(4) DEFAULT '0',
  `date_added` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `page_num` (`page_num`),
  KEY `date_updated` (`date_updated`),
  KEY `page_num_2` (`page_num`,`date_updated`),
  KEY `date_added` (`date_added`),
  KEY `page_num_date_added` (`page_num`,`date_added`)
) ENGINE=MyISAM AUTO_INCREMENT=29922699 DEFAULT CHARSET=utf8;
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTexttvTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('texttv', function (Blueprint $table) {
            // Columns
            $table->id();
            $table->smallInteger('page_num')->nullable();
            $table->binary('page_content');
            $table->dateTime('date_added')->nullable();
            $table->dateTime('date_updated')->nullable();
            $table->integer('next_page')->nullable();
            $table->integer('prev_page')->nullable();
            $table->string('title', 255)->nullable();
            $table->tinyInteger('is_shared')->nullable()->default(0);
            
            // Indexes
            $table->index(['page_num'], 'page_num');
            $table->index(['date_updated'], 'date_updated');
            $table->index(['date_added'], 'date_added');
            $table->index(['page_num', 'date_updated'], 'page_num_2');
            $table->index(['page_num', 'date_added'], 'page_num_date_added');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('texttv');
    }
}
