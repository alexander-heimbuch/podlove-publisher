<?php

namespace Podlove\Modules\Shownotes;

use \Podlove\Modules\Shownotes\Model;
use \Podlove\Modules\Shownotes\Template;

class TemplateExtensions
{
    /**
     * Episode Shownotes (Beta Release only)
     *
     * **Examples**
     * 
     * Display all shownotes in a list.
     *
     * ```
     * <ul>
     * {% for entry in episode.shownotes %}
     *   <li class="psn-entry">
     *     {% if entry.type == "link" %}
     *       {% if entry.icon %}
     *         <img class="psn-icon" src="{{ entry.icon }}" />
     *       {% endif %}
     *       <a class="psn-link" href="{{ entry.url }}">{{ entry.title }}</a>
     *     {% elseif entry.type == "topic" %}
     *       {{ entry.title }}
     *     {% endif %}
     *   </li>
     * {% endfor %}
     * </ul>
     * ```
     * 
     * Group shownotes by topic.
     * 
     * ```
     * {% for topic in episode.shownotes({groupby: "topic"}) %}
     *   <h3>{{ topic.title }}</h3>
     * 
     *   <ul>
     *     {% for entry in topic.entries %}
     *       <li class="psn-entry">
     *         {% if entry.type == "link" %}
     *           {% if entry.icon %}
     *             <img class="psn-icon" src="{{ entry.icon }}"/>
     *           {% endif %}
     *           <a class="psn-link" href="{{ entry.url }}">{{ entry.title }}</a>
     *         {% endif %}
     *       </li>
     *     {% endfor %}
     *   </ul>
     * {% endfor %}
     * ```
     *
     * @accessor
     * @dynamicAccessor episode.shownotes
     */
    public static function accessorEpisodeShownotes($return, $method_name, \Podlove\Model\Episode $episode, $args = [])
    {
        return $episode->with_blog_scope(function () use ($return, $method_name, $episode, $args) {

            $defaults = [
                "groupby" => "topic"
            ];
            $args = wp_parse_args($args, $defaults);

            $entries = Model\Entry::find_all_by_property('episode_id', $episode->id);

            if (!is_array($entries)) {
                return [];
            }

            usort($entries, function ($a, $b) {
                if ($a->position == $b->position)
                    return 0;
                return ($a->position < $b->position) ? -1 : 1;
            });

            if ($args["groupby"] == "topic") {
                $tmp = array_reduce($entries, function ($agg, $item) {
                    if ($item->type == "topic") {

                        $agg['result'][] = [
                            'title' => $item->title,
                            'entries' => []
                        ];

                        $agg['topic_index'] = count($agg['result']) - 1;
                    } else {

                        if ($agg['topic_index'] == null) {
                            $agg['result'][] = [
                                'title' => '',
                                'entries' => []
                            ];
                            $agg['topic_index'] = count($agg['result']) - 1;
                        }

                        $agg['result'][$agg['topic_index']]['entries'][] = new Template\Entry($item);
                    }

                    return $agg;
                }, ['result' => [], 'topic_index' => null]);

                return $tmp['result'];
            } else {
                return array_map(function ($entry) {
                    return new Template\Entry($entry);
                }, $entries);
            }
        });
    }

    /**
     * Check if an episode has shownotes.
     *
     * **Examples**
     *
     * ```
     * {% if episode.hasShownotes %}
     *   Here are some shownotes
     * {% else %}
     *   ¯\_(ツ)_/¯ 
     * {% endif %}
     * ```
     *
     * @accessor
     * @dynamicAccessor episode.hasShownotes
     */
    public static function accessorEpisodeHasShownotes($return, $method_name, \Podlove\Model\Episode $episode)
    {
        return $episode->with_blog_scope(function () use ($return, $method_name, $episode) {
            return Model\Entry::has_shownotes($episode->id);
        });
    }
}