<?php

namespace Grav\Plugin;

use \Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;
use Grav\Common\Grav;

class GravDiagramsPlugin extends Plugin {
    protected $theme;
    protected $hasMermaid;
    protected $active;

    /**
     * @return array
     */
    public static function getSubscribedEvents() {

        return [
            'onPageInitialized' => ['onPageInitialized', 0]
        ];
    }

    public function onPageInitialized(Event $event) {
        $this->config = $this->mergeConfig($event['page']);
        $this->active = $this->config->get('active');
       
        if ($this->active) {
            $this->enable([
                'onPageContentRaw' => ['onPageContentRaw', 0],
                'onTwigSiteVariables' => ['onTwigSiteVariables', 0]
            ]);
        }
    }

    public function onPageContentRaw(Event $event) {
        // Variables
         
        $page = $event['page'];
        $twig = $this->grav['twig'];
        
        $conf = $this->config;
        // Get initial content
        $raw = $page->getRawContent();

        //MERMAID PART
        $match_mermaid = function ($matches) use (&$page, &$twig, &$conf) {
            // Get the matching content
            $search_mermaid = $matches[0];
//             $this->grav['debugger']->addMessage($conf->delimiter);
            // Remove the tab selector
            if ($conf->delimiter === 'fencedcode') {
                $search_mermaid = str_replace("```mermaid", "", $search_mermaid);
                $search_mermaid = str_replace("```", "", $search_mermaid);
            } else {
                $search_mermaid = str_replace("[mermaid]", "", $search_mermaid);
                $search_mermaid = str_replace("[/mermaid]", "", $search_mermaid);
            }
            // Creating the replacement structure
            $replace_header = "<div class='mermaid' style='text-align:$conf->align'>";
            $replace_footer = "</div>";
            $replace_content = $search_mermaid;
            $replace = "$replace_header" . "$replace_content" . "$replace_footer";

            return $replace;
        };

        $raw = $this->parseInjectMermaid($raw, $match_mermaid, $conf->delimiter);

        //APPLY CHANGES
        $page->setRawContent($raw);
    }

    /**
     *  Applies a specific function to the result of the flow's regexp
     */
    protected function parseInjectMermaid($content, $function, $delimiter) {
        // Regular Expression for selection
        if ($delimiter == 'fencedcode') {
            $regex = '/```mermaid([\s\S]*?)```/';
        } else {
            $regex = '/\[mermaid\]([\s\S]*?)\[\/mermaid\]/';
        }

        return preg_replace_callback($regex, $function, $content);
    }

    /**
     * Set needed ressources to display and convert charts
     */
    public function onTwigSiteVariables(Event $event) {
        // Resources for the conversion

        $this->grav['assets']->addJs('plugin://grav-diagrams/js/mermaid.min.js');
        $this->grav['assets']->addJs('plugin://grav-diagrams/js/main.min.js');
    }

}
