<?php

/**
 * This whole thing is a bodge that needs to die.
 *
 * Duplicate the name-match-based subtype identification logic from 2016-site, until it is configured to
 * properly use the [show, season]Subtype field from MyRadio, and MyRadio has a proper GUI for setting them.
 *
 * It sucks, but that's the cost of progress.
 */

namespace MyRadio\Helpers;

function get_subtype_for_show($show_name) {
    $blockMatches = [
        ["ury: early morning", "primetime"],
        ["ury breakfast", "primetime"],
        ["ury lunch", "primetime"],
        ["ury brunch", "primetime"],
        ["URY Brunch", "primetime"],
        ["URY Afternoon Tea:", "primetime"],
        ["URY:PM", "primetime"],
        ["Alumni Takeover:", "primetime"],

        ["ury news", "news"],
        ["ury sports", "news"],
        ["ury football", "news"],
        ["york sport report", "news"],
        ["university radio talk", "news"],
        ["candidate interview night", "news"],
        ["election results night", "news"],
        ["yusu election", "news"],
        ["The Second Half With Josh Kerr", "news"],
        ["URY SPORT", "news"],
        ["URY News & Sport:", "news"],
        ["URY N&S:", "news"],

        ["ury speech", "speech"],
        ["yorworld", "speech"],
        ["in the stalls", "speech"],
        ["screen", "speech"],
        ["stage", "speech"],
        ["game breaking", "speech"],
        ["radio drama", "speech"],
        ["Book Corner", "speech"],
        ["Saturated Facts", "speech"],
        ["URWatch", "speech"],
        ["Society Challenge", "speech"],
        ["Speech Showcase", "speech"],
        ["URY Speech:", "speech"],

        ["URY Music:", "music"],

        ["roses live 20", "event"],
        ["roses 20", "event"],
        ["freshers 20", "event"],
        ["woodstock", "event"],
        ["movember", "event"],
        ["panto", "event"],
        ["101:", "event"],
        ["Vanbrugh Chair Debate", "event"],
        ["URY Does RAG Courtyard Takeover", "event"],
        ["URY Presents", "event"],
        ["URYOnTour", "event"],
        ["URY On Tour", "event"],

        ["YSTV", "collab"],
        ["Nouse", "collab"],
        ["York Politics Digest", "collab"],
        ["Breakz", "collab"],
    ];

    $name = strtolower($show_name);
    foreach ($blockMatches as $match) {
        if (strpos($name, strtolower($match[0])) !== false /* bloody PHP */) {
            return $match[1];
        }
    }
    return 'regular';
}

