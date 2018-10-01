<?php
namespace Grav\Plugin;

use \Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;
use Grav\Common\Grav;
class DiagramsPlugin extends Plugin
{
    protected $theme;
    protected $grav;
    protected $hasFlow;
    protected $hasSequence;
    protected $hasMermaid;
    protected $mergeConfig;
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        $grav = Grav::instance();
        return [
//            'onPluginsInitialized' => ['onPluginsInitialized', 0],
            'onPageInitialized' => ['onPageInitialized', 0],
            'onPageContentRaw' => ['onPageContentRaw', 0],
            'onTwigSiteVariables'   => ['onTwigSiteVariables', 0]
        ];
    }
    public function onPageInitialized(Event $e)
    {
        $this->grav['debugger']->addMessage("onPageInitialized");
        $this->config = $this->mergeConfig($e['page']);
        $this->hasFlow = $this->config->get('flow.enabled');
        $this->hasMermaid = $this->config->get('mermaid.enabled');
        $this->hasSequence = $this->config->get('sequence.enabled');
    }

//    public function onPluginsInitialized(Event $event)
//    {
//        //$this->grav['debugger']->addMessage("onPluginsInitialized");
//        $this->hasFlow = $this->config->get('flow.enabled');
//        $this->hasMermaid = $this->config->get('mermaid.enabled');
//        $this->hasSequence = $this->config->get('sequence.enabled');
//        //$page = $event['page'];
//        //dump($this->config);
//    }
    public function onPageContentRaw(Event $event)
    {
        // Variables
        $this->align = $this->config->get('align');
        
        $page = $event['page'];
        $twig = $this->grav['twig'];

        //$this->mergeConfig = $this->mergeConfig($page);
       // dump($config);
       
        if ($this->config->get('enabled')) {
            $conf = $this->config;
            // Get initial content
            $raw = $page->getRawContent();

            /*****************************
             * SEQUENCE PART
             */

            $matchSequence = function ($matches) use (&$page, &$twig, &$conf) {
                // Get the matching content
                $search_sequence = $matches[0];

                // Remove the tab selector
                $search_sequence = str_replace('[sequence]', '', $search_sequence);
                $search_sequence = str_replace('[/sequence]', '', $search_sequence);

                // Creating the replacement structure
                $replace_header = "<div class='diagram' style='text-align:.$this->align.'>";
                $replace_footer = "</div>";
                $replace_content = $search_sequence;
                $replace = "$replace_header" . "$replace_content" . "$replace_footer";

                return $replace;
            };

            $raw = $this->parseInjectSequence($raw, $matchSequence);

            /*****************************
             * FLOW PART
             */

            $matchFlow = function ($matches) use (&$page, &$twig, &$conf) {
                static $cpt = 0;

                // Get the matching content
                $search_flow = $matches[0];

                // Remove the tab selector
                $search_flow = str_replace("[flow]", "", $search_flow);
                $search_flow = str_replace("[/flow]", "", $search_flow);

                // Creating the replacement structure
//                $replace_header = "<div id=\"canvas_".$cpt."\" class=\"flow\" style=\"text-align:".$this->align."\">";
                $replace_header = "<div id='canvas_.$cpt.' class='flow' style='text-align:.$this->align.'>";
                $cpt++;
                $replace_footer = "</div>";
                $replace_content = $search_flow;
                $replace = "$replace_header" . "$replace_content" . "$replace_footer";

                return $replace;
            };

            $raw = $this->parseInjectFlow($raw, $matchFlow);

            /*****************************
             * MERMAID PART
             */

            $match_mermaid = function ($matches) use (&$page, &$twig, &$conf) {
                // Get the matching content
                $search_mermaid = $matches[0];

                // Remove the tab selector
                if( $conf->get('delimiter') === 'fencedcode'){
                    $search_mermaid = str_replace("```mermaid", "", $search_mermaid);
                    $search_mermaid = str_replace("```", "", $search_mermaid);
                }
                else{
                   $search_mermaid = str_replace("[mermaid]", "", $search_mermaid);
                    $search_mermaid = str_replace("[/mermaid]", "", $search_mermaid);
                }
                // Creating the replacement structure
                $replace_header = "<div class='mermaid' style='text-align:$this->align'>";
                $replace_footer = "</div>";
                $replace_content = $search_mermaid;
                $replace = "$replace_header" . "$replace_content" . "$replace_footer";

                return $replace;
            };

            $raw = $this->parseInjectMermaid($raw, $match_mermaid, $conf->get('delimiter'));

            /*****************************
             * APPLY CHANGES
             */
            $page->setRawContent($raw);
        }
    }

    /**
     *  Applies a specific function to the result of the sequence's regexp
     */
    protected function parseInjectSequence($content, $function)
    {
        // Regular Expression for selection
        $regex = '/\[sequence\]([\s\S]*?)\[\/sequence\]/';
        return preg_replace_callback($regex, $function, $content);
    }

    /**
     *  Applies a specific function to the result of the flow's regexp
     */
    protected function parseInjectFlow($content, $function)
    {
        // Regular Expression for selection
        $regex = '/\[flow\]([\s\S]*?)\[\/flow\]/';
        return preg_replace_callback($regex, $function, $content);
    }

    /**
     *  Applies a specific function to the result of the flow's regexp
     */
    protected function parseInjectMermaid($content, $function, $delimiter)
    {
         $this->grav['debugger']->addMessage($this->config);
        // Regular Expression for selection
        if($delimiter =='fencedcode') 
        {
            $regex = '/```mermaid([\s\S]*?)```/';
        }
        else{
            $regex = '/\[mermaid\]([\s\S]*?)\[\/mermaid\]/';
        }
        
        return preg_replace_callback($regex, $function, $content);
    }

    /**
     * Set needed ressources to display and convert charts
     */
    public function onTwigSiteVariables()
    {
        // Variables
        //dump("onTwigSiteVariables");
        $this->grav['debugger']->addMessage("onTwigSiteVariables");

         $this->grav['debugger']->addMessage($this->config);
        // Resources for the conversion
        if($this->hasFlow ||  $this->hasMermaid || $this->hasSequence)
        {
           
            
            //$this->gantt_axis = $this->config->get('gantt.axis');
            
            $this->grav['assets']->addJs('plugin://diagrams/js/underscore-min.js');
            $this->grav['assets']->addJs('plugin://diagrams/js/lodash.min.js');
            $this->grav['assets']->addJs('plugin://diagrams/js/raphael-min.js');

            // Used to start the conversion of the div "diagram" when the page is loaded
            $init = "$(document).ready(function() {";
            if($this->hasMermaid)
            {
                $this->grav['assets']->addJs('plugin://diagrams/js/mermaid.min.js');
                if($this->config->get('builtin-css')){
                     $this->grav['assets']->addCss('plugin://diagrams/css/mermaid.css');
                }
               
                $init .= "mermaid.initialize({startOnLoad:true});
                          mermaid.ganttConfig = {axisFormatter: [['".$this->config->get('mermaid.gantt.axis')."', function (d){return d.getDay() == 1;}]]};";
            }
            if($this->hasSequence)
            {
                $this->grav['assets']->addJs('plugin://diagrams/js/sequence-diagram-min.js');
                $init .= "$(\".diagram\").sequenceDiagram({theme: '".$this->config->get('theme')."'});";
            }
            if($this->hasFlow)
            {
                $this->grav['assets']->addJs('plugin://diagrams/js/flowchart-latest.js');
                
                $this->font_size = $this->config->get('flow.font.size');
                $this->font_color = $this->config->get('flow.font.color');
                $this->line_color = $this->config->get('flow.line.color');
                $this->element_color = $this->config->get('flow.line.color');
                $this->condition_yes = $this->config->get('flow.condition.yes');
                $this->condition_no = $this->config->get('flow.condition.no');
                $init .= "
                            var parent = document.getElementsByClassName('flow');
                            for(var i=0;i<parent.length;i++) {
                                var data = parent[i].innerHTML.replace(/&lt;/g, '<').replace(/&gt;/g, '>');
                                parent[i].innerHTML = \"\";
                                var chart = flowchart.parse(data);
                                chart.drawSVG('canvas_'+i, {
                                    'font-size': ".$this->font_size.",
                                    'font-color': '".$this->font_color."',
                                    'line-color': '".$this->line_color."',
                                    'element-color': '".$this->element_color."',
                                    'yes-text': '".$this->condition_yes."',
                                    'no-text': '".$this->condition_no."',

                                    // More informations : http://flowchart.js.org
                                    'flowstate' : {
                                        'simple': {'fill' : '#FFFFFF'},
                                        'positive': {'fill' : '#387EF5'},
                                        'success': { 'fill' : '#9FF781'},
                                        'invalid': {'fill' : '#FA8258'},
                                        'calm': {'fill' : '#11C1F3'},
                                        'royal': {'fill' : '#CF86E9'},
                                        'energized': {'fill' : 'F3FD60'},
                                    }
                                });
                            }";
                        
            }
           $init .=  "});";
            $this->grav['assets']->addInlineJs($init);
        }
    }
}
