<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Suin\RSSWriter\Channel;
use Suin\RSSWriter\Feed;
use Suin\RSSWriter\Item;
use Config;
use Storage;

/**
 * Class TransformCommand
 * @package App\Console\Commands
 */
class TransformCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'feed:transform';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reads feed of latest hr2 Jazz episodes and transforms it to a useful format.';

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $originalXml = file_get_contents(Config::get('hr2jazzfeed.url'));

        $feed = new Feed();
        $channel = new Channel();
        $channel
            ->title("hr2 Jazz")
            ->description("Transformed hr2 Jazz Feed")
            ->url('http://localhost')
            ->appendTo($feed);

        $xmlReader = new \XMLReader();
        $xmlReader->XML(utf8_encode($originalXml));
        $item = new Item();
        while ($xmlReader->read()) {
            if ($xmlReader->nodeType == \XMLReader::ELEMENT) {
                if ($xmlReader->name == 'item') {
                    $item = new Item();
                } elseif ($xmlReader->name == 'title') {
                    $item->title($xmlReader->readString());
                } elseif ($xmlReader->name == 'jwplayer:source') {
                    $item->enclosure($xmlReader->getAttribute('file'));
                } elseif ($xmlReader->name == 'jwplayer:image') {
                    $item->appendTo($channel);
                }
            }
        }
        $xmlReader->close();

        Storage::disk('local')->put('feed.rss', $feed->render());
    }
}
