<?php

$faIconPickerSettings = [
    // rows in picker widget
    'rows'                  => 6,
    // columns in picker widget
    'columns'               => 5,
    // number of pages (cols * rows) to preload per load (top and bottom edge)
    'offset'                => 1,
    // show weight selector on edge of picker widget
    'weight-selector'       => true,
    // when mousing over an icon, show infos with name, search terms, versions and more
    'details-on-hover'      => true,
    // if true, picker is draggable (saved for dedicated target and for current page load only), double click resets to automatic positions
    'movable'               => false,
    // show "clear target" button
    'clear-target'          => true,
    // adding an "x" button top right of the
    'close-with-button'     => false,
    // single or multiple icons per input
    'multiple'              => false,
    // allowed weight to see and pick (T = Thin, L = Light, R = Regular, S = Solid, D = Duotone, B = Brand) - concat in 1 string
    'weights'               => 'TLRSDB',
    // on icon select, add font weight class (for example "far" or "fad")
    'add-weight'            => false,
    // font weight in picker matrix
    'preview-weight'        => 'R',
    // data field to insert: default "name", else available: ["code" (for example "f042"), "svg", "label"]
    'insert-value'          => 'name',
    // sorting in picker widget (available: id, name, label, code, createdate)
    'sort-by'               => 'name',
    // sorting direction ("asc" or "desc")
    'sort-direction'        => 'asc',
    // hide search on top of widget
    'hide-search'           => false,
    // latest selections on top of picker is stored in "latest used" section, hide it?
    'hide-latest-used'      => false,
    // latest used stack size
    'latest-used-max'       => 10,
    // custom list of allowed icons; allows placeholders with '*'
    // examples: question,user*,*virus*,*-up - loading icon `question` explicitly, all icons beginning with `user`, all icons contoning `virus` anywhere and all icons, ending with `-up`
    'icons'                 => null,
    // custom style class on whole picker widget
    'class'                 => '',
    // custom style class for single icons
    'icon-class'            => '',
    // event handler: fired before icon is selected and inserted into target field | first param is button object, second param is target element (object) | cancel selection process with "return false" in callback function
    'onbeforeselect'        => null,
    // event handler: fired after icon was selected and inserted into target field | first param is icon, second param is delete flag (false = selected, true = unselected), third param is target element (object)
    'onselect'              => null,
    // event handler: before opening/showing widget | first param is target element (object)
    'onbeforeshow'          => null,
    // event handler: after opening/showing widget and showing 1. page or current view | first param is target element (object)
    'onshow'                => null,
    // event handler: before closing/hiding widget | first param is target element (object)
    'onbeforehide'          => null,
    // event handler: after closing/hiding widget | first param is target element (object)
    'onhide'                => null,
];