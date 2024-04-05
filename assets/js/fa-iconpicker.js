/**
 * FA picker
 */
class FAPicker {
    config = null;
    target = null;
    widget = null;
    details = null;

    // const
    iconLength = 38; // px
    scrollbarSpace = 10; // px
    fadeInSpeed = 300; // ms
    iconTooltipDelay = 1000; // ms
    iconTooltipTimeout = null;
    searchKeyUpDelay = 500; // ms
    searchTimeout = null;
    scrollPagingDelay = 200; // ms
    scrollPagingTimeout = null;
    latestUsedCookieName = 'rex-fa-iconpicker-latest';
    latestUsedLimit = 10;

    tooltipSettings = {
        delay: {
            show: 500,
            hide: 0
        }
    };

    // current settings
    maxPages = 0;
    pagesLoaded = 0;
    activeIcons = {};
    availableWeights = [];
    iconRequest = null;
    scrollPagingLastPos = 0;

    /**
     * init / constructor
     * @param target DOM Object
     * @param config user settings
     */
    constructor(target, config = {}) {
        // check if already initialized
        if(typeof(target.FAPicker) != "undefined") {
            return;
        }

        // create reference to get picker from input
        this.target = $(target);
        this.target.attr('autocomplete', 'off');

        target.FAPicker = this;

        // set picker config and apply custom attribute settings
        this.applyConfig(config);

        if(this.config['clear-target']) {
            this.target.prop("readonly", true);
        }

        // create widget
        this.initWidget();

        // put initial values into internal icon stack
        this.syncIconSelection();
    }

    /**
     * set configs
     * @param config user settings
     */
    applyConfig(config = {}) {
        // set settings and merge with
        let currentSettings = this.config = Object.assign({}, FAPickerSettings);

        for (const [key, value] of Object.entries(currentSettings)) {
            let customSetting = 'data-fa-'+ key;

            if($(this.target).attr(customSetting) != undefined) {
                switch(typeof(value)) {
                    case 'boolean':
                        currentSettings[key] = (parseInt($(this.target).attr(customSetting)) == 1 ? true : false);
                        break;

                    case 'number':
                        currentSettings[key] = parseInt($(this.target).attr(customSetting));
                        break;

                    default:
                        // if(typeof(value) == null && /^data-fa-on/.test(customSetting)) {
                        // }
                        currentSettings[key] = $(this.target).attr(customSetting);
                        break;
                }
            }
        }

        // apply user settings
        currentSettings = Object.assign(currentSettings, config);

        // apply
        this.config = currentSettings;
    }

    /**
     * init overlay
     */
    initWidget() {
        const _self = this;

        let _widget = this.widget = $(
            '<div class="fa-iconpicker-widget weight-'+ this.config['preview-weight'] +' hidden">' +
                '<div class="fa-iconpicker-inner">'+
                    '<div class="fa-iconpicker-search hidden"><input type="text" placeholder="'+ FAPickerAddonI18N['fa_iconpicker_widget_search_placeholder'] +'"></div>' +
                    '<div class="fa-iconpicker-latest hidden">' +
                        '<label>'+ FAPickerAddonI18N['fa_iconpicker_widget_label_last_used'] +'</label>' +
                        '<div class="fa-iconpicker-latest-wrapper"></div>' +
                    '</div>' +
                    '<div class="fa-iconpicker-icons">' +
                        '<label>'+ FAPickerAddonI18N['fa_iconpicker_widget_label_icons'] +' <span class="fa-iconpicker-icons-count"></span></label>' +
                        '<div class="fa-iconpicker-icons-wrapper">' +
                            '<div class="fa-iconpicker-pager"></div>' +
                            '<div class="fa-iconpicker-no-icons hidden">'+ FAPickerAddonI18N['fa_iconpicker_widget_no_icons'] +'</div>' +
                        '</div>' +
                    '</div>' +
                '</div>'+
                '<div class="fa-iconpicker-package-info">'+ FAPickerPackage.version +' '+ FAPickerPackage.variant +'<span class="hidden">'+ FAPickerAddonI18N['fa_iconpicker_packages_subset'] +'</span></div>' +
                '<div class="fa-iconpicker-close hidden"><button data-toggle="tooltip" data-placement="right" title="'+ FAPickerAddonI18N['fa_iconpicker_widget_close_tooltip'] +'"><i class="fa far fa-times"></i></button></div>' +
                '<div class="fa-iconpicker-move hidden"><button><i class="fa far fa-arrows"></i></button></div>' +
                '<div class="fa-iconpicker-clear hidden"><button data-toggle="tooltip" data-placement="right" title="'+ FAPickerAddonI18N['fa_iconpicker_widget_clear_tooltip'] +'"><i class="fa far fa-ban"></i></button></div>' +
                '<div class="fa-iconpicker-weights hidden"></div>' +
            '</div>');
        //  data-toggle="tooltip" data-placement="right" title="'+ FAPickerAddonI18N['fa_iconpicker_widget_move_tooltip'] +'"

        this.widget.appendTo("body");

        // subset?
        if(FAPickerPackage.subset != null) {
            this.widget.find(".fa-iconpicker-package-info span").removeClass("hidden");
        }

        // add custom class
        if($.trim(this.config.class) != "") {
            this.widget.addClass(this.config.class);
        }

        // set dimensions
        this.widget.find(".fa-iconpicker-icons .fa-iconpicker-icons-wrapper").css('width', ((this.config.columns * this.iconLength) + this.scrollbarSpace) +'px');
        this.widget.find(".fa-iconpicker-icons .fa-iconpicker-icons-wrapper").css('height', (this.config.rows * this.iconLength) +'px');

        // handle search
        if(!this.config['hide-search']) {
            this.widget.addClass("with-search");
            this.widget.find(".fa-iconpicker-search").removeClass("hidden");
            this.initSearch();
        }

        // handle latest used
        if(!this.config['hide-latest-used']) {
            this.widget.addClass("with-latest-used");
            this.widget.find(".fa-iconpicker-latest").css('width', ((this.config.columns * this.iconLength) + this.scrollbarSpace) +'px');
            this.widget.find(".fa-iconpicker-latest").removeClass("hidden");

            this.widget.find(".fa-iconpicker-latest").scroll(function(e){
                if(_self.iconTooltipTimeout != null) {
                    clearTimeout(_self.iconTooltipTimeout);
                    _self.iconTooltipTimeout = null;
                }

                $(_self.details).remove();
            });
        }

        // handle close button
        if(this.config['close-with-button']) {
            this.widget.addClass("with-close-button");
            this.widget.find(".fa-iconpicker-close").removeClass("hidden").children("button").click(function() {
                _self.hideWidget();
            });
        } else {
            $(document).click((event) => {
                if (!$(event.target).closest(_self.widget).length &&
                    !$(event.target).closest(_self.target).length &&
                    !$(event.target).closest(_self.details).length &&
                    !$(event.target).closest(_self.widget).find(".fa-iconpicker-latest-wrapper").length &&
                    !_self.widget.hasClass("hidden")
                ) {
                    _self.hideWidget();
                }
            });
        }

        // handle move (dnd) button
        if(this.config['movable']) {
            this.widget.addClass("with-move-button");

            let dragButton = this.widget.find(".fa-iconpicker-move");

            dragButton.mousedown(startDragPicker);

            function startDragPicker(e) {
                document.onmouseup = stopDragPicker;
                document.onmousemove = dragPicker;

                _self.widget.data("drag-start-x", e.clientX);
                _self.widget.data("drag-start-y", e.clientY + $("html").scrollTop());
                _self.widget.data("drag-start-offset-x", e.clientX - _self.widget.offset().left);
                _self.widget.data("drag-start-offset-y", e.clientY + $("html").scrollTop() - _self.widget.offset().top);
                _self.widget.addClass("moving");
            }

            function dragPicker(e) {
                e = e || window.event;
                e.preventDefault();

                // set the picker's new position
                _self.widget.css("left", (e.clientX - _self.widget.data("drag-start-offset-x")) + "px");
                _self.widget.css("top", (e.clientY - _self.widget.data("drag-start-offset-y")) + "px");
            }

            // stop moving when mouse button is released:
            function stopDragPicker() {
                document.onmouseup = null;
                document.onmousemove = null;

                _self.widget.addClass("moved");
                _self.widget.removeClass("moving");
            }

            this.widget.find(".fa-iconpicker-move").removeClass("hidden").children("button").dblclick(function() {
                _self.widget.removeClass("moved moving");
                _self.setPickerPosition();
            });
        }

        // handle clear button
        if(this.config['clear-target']) {
            this.widget.addClass("with-clear-button");

            this.widget.find(".fa-iconpicker-clear").removeClass("hidden").children("button").click(function() {
                _self.target.val("");
                _self.activeIcons = {};

                // remove active class from all buttons
                _self.widget.find(".fa-iconpicker-pager .fa-iconpicker-page button").removeClass("active");
                _self.widget.find(".fa-iconpicker-latest-wrapper button").removeClass("active");

                $(_self.details).find("button").removeClass("active");
            });
        }

        // handle paging by scrolling
        this.initPaging();

        // create weight selector and callbacks
        if(this.config['weight-selector']) {
            this.loadAvailableWeights();
        }

        // bind events to target
        this.target.bind("focusin", function(){
            _self.showWidget();
        });

        // enable delayed tooltips for picker tools
        this.widget.find('[data-toggle="tooltip"]').tooltip(this.tooltipSettings);

        // on document scroll move active picker
        $(document).scroll(function(e) {
            if(!_self.widget.hasClass("hidden")) {
                _self.setPickerPosition();
            }

            $(_self.details).remove();
        });

        // load icons
        this.loadIcons();
    }

    /**
     * show picker widget
     */
    showWidget() {
        const _self = this;
        let ret = true;

        if(_self.config.onbeforeshow) {
            if (typeof (_self.config.onbeforeshow) == "function") {
                ret =_self.config.onbeforeshow(_self.target[0]);
            } else if (typeof (_self.config.onbeforeshow) == "string") {
                ret = window[_self.config.onbeforeshow](_self.target[0]);
            }
        }

        if(ret === false) {
            return;
        }

        if(this.widget.hasClass("hidden")) {
            this.widget.removeClass("hidden");
            this.widget.css("opacity", 0);
            this.setPickerPosition();

            this.widget.animate({
                opacity: 1
            }, this.fadeInSpeed, function() {
                if(_self.config.onshow) {
                    if (typeof (_self.config.onshow) == "function") {
                        ret =_self.config.onshow(_self.target[0]);
                    } else if (typeof (_self.config.onshow) == "string") {
                        ret = window[_self.config.onshow](_self.target[0]);
                    }
                }
            });
        } else {
            this.setPickerPosition();
        }
    }

    /**
     * hide picker widget
     */
    hideWidget() {
        const _self = this;
        let ret = true;

        if(_self.config.onbeforehide) {
            if (typeof (_self.config.onbeforehide) == "function") {
                ret =_self.config.onbeforehide(_self.target[0]);
            } else if (typeof (_self.config.onbeforehide) == "string") {
                ret = window[_self.config.onbeforehide](_self.target[0]);
            }
        }

        if(ret === false) {
            return;
        }

        this.widget.addClass("hidden");

        if(this.iconTooltipTimeout != null) {
            clearTimeout(this.iconTooltipTimeout);
            this.iconTooltipTimeout = null;
        }

        $(this.details).remove();

        if(_self.config.onhide) {
            if (typeof (_self.config.onhide) == "function") {
                ret =_self.config.onhide(_self.target[0]);
            } else if (typeof (_self.config.onhide) == "string") {
                ret = window[_self.config.onhide](_self.target[0]);
            }
        }
    }

    /**
     * calculate position below/above target
     */
    setPickerPosition() {
        // prevent action when manually moved
        if(this.widget.hasClass("moved")) {
            return;
        }

        let targetPosition = this.target.offset();
        let top = targetPosition.top + this.target.outerHeight() + 5 - $("html").scrollTop();

        if((top + this.widget.height()) > $(window).height()) {
            top = targetPosition.top - this.widget.height() - 7 - $("html").scrollTop();
        }

        this.widget.css("left", targetPosition.left + "px");
        this.widget.css("top", top + "px");
    }

    /**
     * load page(s) with icons
     * @param page(int or null)
     */
    loadIcons(page = null) {
        const urlParams = new URLSearchParams(window.location.search);
        const _self = this;

        let pager = _self.widget.find(".fa-iconpicker-pager");
        let noIcons = _self.widget.find(".fa-iconpicker-no-icons");

        // cancel pending request
        if(_self.iconRequest && _self.iconRequest.readyState != 4) {
            _self.iconRequest.abort();
            _self.widget.find(".fa-iconpicker-pager .fa-iconpicker-page.loading").removeClass("loading");
        }

        if(page == null) {
            _self.widget.addClass("loading");
            pager.html("");
        } else {
            // set target pages loading
            for(let i = (page - _self.config.offset) ; i <= (page + _self.config.offset) ; i++) {
                _self.widget.find(".fa-iconpicker-pager .fa-iconpicker-page[data-index="+ i +"]:not(.filled)").addClass("loading");
            }
        }

        let data = Object.assign({
            "page": urlParams.get('page'),
            "rex-api-call": "fa_iconpicker",
            "icon-page": page,
            "icon-search": $.trim(_self.widget.find(".fa-iconpicker-search").children().val())
        }, this.config);

        _self.iconRequest = $.ajax({
            url: 'index.php',
            type: 'POST',
            async: true,
            cache: false,
            dataType: 'JSON',
            data: data,

            success: function(data) {
                let iconsPerPage = _self.config.rows * _self.config.columns;
                let targetPageIndex = 0;

                // create pages
                if(page == null) {
                    _self.maxPages = Math.ceil(data.iconCount / (_self.config.columns * _self.config.rows));

                    for(let i=0 ; i<_self.maxPages ; i++) {
                        $('<div class="fa-iconpicker-page" data-index="'+ i +'" data-page="'+ (i+1) + ' / ' + _self.maxPages +'" style="height: '+ (_self.config.rows * _self.iconLength) +'px;"></div>').appendTo(pager);
                    }
                } else {
                    targetPageIndex = page - _self.config.offset;

                    // unset target pages loading
                    for(let i = (page - _self.config.offset) ; i <= (page + _self.config.offset) ; i++) {
                        _self.widget.find(".fa-iconpicker-pager .fa-iconpicker-page[data-index="+ i +"]:not(.filled)").removeClass("loading");
                    }
                }

                // add icons to pages
                let targetPage = pager.children('[data-index='+ targetPageIndex +']');

                if(data.icons.length > 0) {
                    targetPage.addClass("filled");
                }

                noIcons.toggleClass("hidden", data.icons.length > 0);

                for(let i=0 ; i<data.icons.length ; i++) {
                    if(i > 0 && i % iconsPerPage == 0) {
                        targetPageIndex++;
                        targetPage = pager.children('[data-index='+ targetPageIndex +']');
                        targetPage.addClass("filled");
                    }

                    let searchTerms = JSON.parse(data.icons[i].searchterms);

                    $('<button ' +
                        'data-id="'+ data.icons[i].id +'" ' +
                        'data-name="'+ data.icons[i].name +'" ' +
                        'data-code="'+ data.icons[i].code +'" ' +
                        'data-label="'+ data.icons[i].label +'" ' +
                        'data-svg-hash="'+ data.icons[i]['svg-hash'] +'" ' +
                        'data-search-terms="'+ searchTerms.join(", ") +'" ' +
                        'data-weights="'+ data.icons[i].allweights +'">' +
                            '<i class="fa'+ _self.config['preview-weight'].toLowerCase() +' fa-'+ data.icons[i].name +'"></i>' +
                      '</button>')
                        .appendTo(targetPage);
                }

                // bind events to buttons
                let buttons = pager.find("button:not(.initialized)");

                // bind selector
                buttons.click(function(e) {
                    e.stopPropagation();

                    let ret = true;

                    // before select event
                    if(_self.config.onbeforeselect) {
                        if (typeof (_self.config.onbeforeselect) == "function") {
                            ret = _self.config.onbeforeselect(this, _self.target[0]);
                        } else if (typeof (_self.config.onbeforeselect) == "string") {
                            ret = window[_self.config.onbeforeselect](this, _self.target[0]);
                        }
                    }

                    if(ret === false) {
                        return;
                    }

                    _self.selectIcon($(this));
                });

                // bind details
                if(_self.config['details-on-hover']) {
                    buttons.mouseenter(function(e) {
                        _self.showIconDetails($(this));
                    });

                    buttons.mouseleave(function(e){
                        if(_self.iconTooltipTimeout != null) {
                            clearTimeout(_self.iconTooltipTimeout);
                            _self.iconTooltipTimeout = null;
                        }
                    });
                }

                buttons.addClass("initialized");

                // sync and update
                _self.syncIconSelection(function(){
                    _self.updateTargetValue();
                    _self.updateLatestUsed();
                });

                // insert count
                _self.widget.find(".fa-iconpicker-icons-count").html(data.iconCount > 0 ? "(" + data.iconCount + ")" : "");

                // disable loading
                _self.widget.removeClass("loading");
            }
        });
    }

    /**
     * load available weights
     * @param page
     */
    loadAvailableWeights() {
        const urlParams = new URLSearchParams(window.location.search);
        const _self = this;

        let data = {
            "page": urlParams.get('page'),
            "rex-api-call": "fa_iconpicker",
            "method": "get-available-weights",
            "weights": _self.config.weights
        };

        $.ajax({
            url: 'index.php',
            type: 'POST',
            async: true,
            cache: false,
            dataType: 'text',
            data: data,

            success: function(data) {
                let weights = _self.config.weights;

                if($.trim(data) != "") {
                    weights = _self.availableWeights = data;
                }

                let $weightSelector = _self.widget.children(".fa-iconpicker-weights");
                $weightSelector.removeClass("hidden");

                for(let i=0 ; i<weights.length ; i++) {
                    $('<button value="'+ weights[i] +'" '+
                        (_self.config['preview-weight'] == weights[i] ? 'class="active"' : '') +
                        'data-toggle="tooltip" data-placement="left" title="'+ FAPickerAddonI18N['fa_iconpicker_widget_weight_tooltip_' + weights[i]] +'">'+
                        weights[i] +
                      '</button>').appendTo($weightSelector);

                    $weightSelector.children('button[value="'+ weights[i] +'"]').click(function(){
                        _self.setPreviewWeight($(this).val());
                    });
                }

                // check if avaiblable weights match desired preview weight, revert to first if not
                if($weightSelector.children('button.active').length == 0) {
                    $weightSelector.children('button:first-child').click();
                }

                // enable delayed tooltips for picker tools
                $weightSelector.find('[data-toggle="tooltip"]').tooltip(_self.tooltipSettings);
            }
        });
    }

    /**
     * load icon svg
     * @param iconID
     */
    async loadSVG(iconID) {
        const urlParams = new URLSearchParams(window.location.search);
        const _self = this;

        let data = {
            "page": urlParams.get('page'),
            "rex-api-call": "fa_iconpicker",
            "method": "get-icon-svg",
            "icon-id": iconID
        };

        let result;

        try {
            result = await $.ajax({
                url: 'index.php',
                type: 'POST',
                async: true,
                cache: false,
                dataType: 'text',
                data: data
            });

            return result;
        } catch (error) {
            console.error(error);
        }
    }

    /**
     * init search
     */
    initSearch() {
        let search = this.widget.find(".fa-iconpicker-search:not(.hidden)").children();
        const _self = this;

        if(search.length == 0) {
            return;
        }

        search.keyup(function(e) {
            if (_self.searchTimeout != null) {
                clearTimeout(_self.searchTimeout);
                _self.searchTimeout = null;
            }

            _self.searchTimeout = setTimeout(function() {
                _self.loadIcons();
            }, _self.searchKeyUpDelay);
        });
    }

    /**
     * init paging by scroll pos
     */
    initPaging() {
        const _self = this;

        this.widget.find(".fa-iconpicker-icons-wrapper").scroll(function(e){
            if(_self.scrollPagingTimeout != null) {
                clearTimeout(_self.scrollPagingTimeout);
                _self.scrollPagingTimeout = null;
            }

            if(_self.iconTooltipTimeout != null) {
                clearTimeout(_self.iconTooltipTimeout);
                _self.iconTooltipTimeout = null;
            }

            // clear tooltips
            $(_self.details).remove();

            let pages = _self.widget.find(".fa-iconpicker-icons-wrapper .fa-iconpicker-pager .fa-iconpicker-page");
            let direction = (_self.scrollPagingLastPos < this.scrollTop ? 'down' : 'up');
            let scrollTop = _self.scrollPagingLastPos = this.scrollTop;

            _self.scrollPagingTimeout = setTimeout(function(){
                // detect closest page
                let pageFound = false;

                pages.each(function(idx){
                    if(pageFound !== false) {
                        return;
                    }

                    if((direction == 'down' && ($(this).position().top - scrollTop) > 0) ||
                       (direction == 'up' && ($(this).position().top - scrollTop + (_self.config.rows * _self.iconLength)) > 0) ||
                       (
                           ($(this).position().top - scrollTop + (_self.config.rows * _self.iconLength / 2)) >= 0 &&
                           ($(this).position().top - scrollTop + (_self.config.rows * _self.iconLength / 2)) <= (_self.config.rows * _self.iconLength)
                       ) /* fallback condition */
                    ) {
                        pageFound = ($(this).hasClass("filled") ? true : parseInt($(this).attr("data-index")));
                        return;
                    }
                });

                if(pageFound !== false && pageFound !== true) {
                    _self.loadIcons(pageFound);
                }
            }, _self.scrollPagingDelay);
        });
    }

    /**
     * set preview weight
     */
    setPreviewWeight(weight) {
        let weightSelection = this.widget.children(".fa-iconpicker-weights").children("button");

        if(!this.widget.children(".fa-iconpicker-weights").hasClass("hidden") && weightSelection.filter(".active").val() == weight) {
            return;
        }

        weightSelection.removeClass("active");
        weightSelection.filter("[value="+ weight +"]").addClass("active");

        // set new preview weight
        let lastWeight = this.config['preview-weight'];
        this.config['preview-weight'] = weight;

        // replace preview weight class in picker widget wrapper
        this.widget.attr('class', function(i, c){
            return c.replace(/(^|\s)weight-\S+/g, '');
        });

        this.widget.addClass("weight-"+ weight);

        // search new list on subset scenarios and when switing from or to brand icons
        if(FAPickerPackage.subset != null || (lastWeight != "B" && weight == "B") || (lastWeight == "B" && weight != "B")) {
            this.loadIcons();
        } else {
            this.widget.find(".fa-iconpicker-pager button i").toggleClass("fad", (weight == "D"));
            this.updateTargetValue();
        }

        // TODO: add callback "onpreviewweightchange"
    }

    /**
     * (un)select icon
     * @param button
     */
    selectIcon(button) {
        if(button.hasClass("loading")) {
            return;
        }

        const _self = this;

        let isDelete = false;
        let name = button.attr("data-name");
        let iconValue = (_self.config['insert-value'] == "svg" ? button.attr("data-svg-hash") : button.attr("data-"+ this.config['insert-value']));

        new Promise((resolve, reject) => {
            if(_self.config['insert-value'] != "svg" || button.hasClass("active")) {
                resolve();
                return;
            }

            button.addClass("loading");

            _self.loadSVG(button.attr("data-id")).then(function(svg) {
                iconValue = svg;
                button.removeClass("loading");
                resolve();
            });
        }).then(() => {
            // reset value
            if(!_self.config.multiple) {
                _self.target.val("");
            }

            // remove active class from all buttons
            _self.widget.find(".fa-iconpicker-pager .fa-iconpicker-page button").removeClass("active");
            $(_self.details).find("button").removeClass("active");

            // add icon data to target
            if(_self.activeIcons[name] == undefined) {
                if(!_self.config.multiple) {
                    _self.activeIcons = {};
                }

                _self.activeIcons[name] = (_self.config['insert-value'] == "name" ? "fa-" : "") + iconValue;
            }
            // remove icon from selection
            else {
                delete _self.activeIcons[name];
                isDelete = true;
            }

            let callback = function() {
                if(_self.config.onselect) {
                    if (typeof (_self.config.onselect) == "function") {
                        _self.config.onselect(name, isDelete, _self.target[0]);
                    } else if (typeof (_self.config.onselect) == "string") {
                        window[_self.config.onselect](name, isDelete, _self.target[0]);
                    }
                }

                _self.updateLatestUsed(!isDelete ? name : null);
            };

            _self.updateTargetValue(callback);
        });
    }

    /**
     * sync current set of loaded icons with local stack
     * @param callback function
     */
    syncIconSelection(callback = function(){}) {
        const _self = this;

        let targetVal = (_self.config['insert-value'] == "svg" ? _self.target.val().split("</svg>") : _self.target.val().replace(/^fa[tlrsdb]\s/, '').split(" "));
        let targetValLength = targetVal.length;
        let buttons = _self.widget.find(".fa-iconpicker-pager .fa-iconpicker-page button");
        let searchAttr = (_self.config['insert-value'] == "svg" ? 'svg-hash' : _self.config['insert-value']);

        targetVal.forEach(function(iconValue, idx) {
            iconValue = $.trim(iconValue);

            if(iconValue == "") {
                targetValLength--;
                return;
            }

            if(_self.config['insert-value'] == "svg") {
                iconValue += '</svg>';

                _self.sha1(iconValue).then(function (sha1Value) {
                    if (buttons.filter('[data-' + searchAttr + '="' + sha1Value + '"]').length) {
                        _self.activeIcons[buttons.filter('[data-' + searchAttr + '="' + sha1Value + '"]').attr("data-name")] = iconValue;
                    }

                    // add +2 here because single item always breaks in 2 (so there is always +1 comapred to normale values)
                    if(idx == targetValLength - 2) {
                        callback();
                    }
                });
            } else {
                iconValue = (_self.config['insert-value'] == 'name' ? iconValue.replace(/^fa-/, '') : iconValue);

                if (buttons.filter('[data-' + searchAttr + '="' + iconValue + '"]').length) {
                    _self.activeIcons[buttons.filter('[data-' + searchAttr + '="' + iconValue + '"]').attr("data-name")] = (_self.config['insert-value'] == 'name' ? 'fa-' : '') + iconValue;
                }

                if(idx == targetValLength - 1) {
                    callback();
                }
            }
        });

        if($.trim(targetVal) == "") {
            callback();
        }
    }

    async sha1(value) {
        const buffer = new TextEncoder('utf-8').encode(value);
        const digest = await crypto.subtle.digest('SHA-1', buffer);

        // Convert digest to hex string
        return await Array.from(new Uint8Array(digest)).map(x => x.toString(16).padStart(2, '0')).join('');
    }

    /**
     * get unsynced icons (icons inserted in target input but button not (yet) visible)
     */
    async getUnsyncedIcons() {
        const _self = this;

        let result = [];
        let value = $.trim(_self.target.val());
        let targetVal = (_self.config['insert-value'] == "svg" ? value.split("</svg>") : value.replace(/^fa[tlrsdb]\s/, '').split(" "));
        let buttons = _self.widget.find(".fa-iconpicker-pager .fa-iconpicker-page button");
        let searchAttr = (_self.config['insert-value'] == "svg" ? 'svg-hash' : _self.config['insert-value']);

        return await new Promise(resolve => {
            if(value == "") {
                resolve(result);
            }

            targetVal.forEach(function(iconValue, idx) {
                iconValue = $.trim(iconValue);

                if(iconValue == "") {
                    return;
                }

                if(_self.config['insert-value'] == "svg") {
                    iconValue += '</svg>';

                    _self.sha1(iconValue).then(function (sha1Value) {
                        if (!buttons.filter('[data-' + searchAttr + '="' + sha1Value + '"]').length) {
                            result.push(iconValue);
                        }

                        if(idx == targetVal.length - 2) {
                            resolve(result);
                        }
                    });
                } else {
                    let buttonValue = (_self.config['insert-value'] == 'name' ? iconValue.replace(/^fa-/, '') : iconValue);

                    if(!buttons.filter('[data-'+ searchAttr +'="'+ buttonValue +'"]').length) {
                        result.push(iconValue);
                    }

                    if(idx == targetVal.length - 1) {
                        resolve(result);
                    }
                }
            });
        });
    }

    /**
     * update input/textarea
     * @param callback can be passed from "selectIcon" method to hook event handler after inserting new value
     */
    updateTargetValue(callback = function(){}) {
        const _self = this;
        let insertValue = this.config['insert-value'];
        let delimiter = (insertValue == 'svg' ? '' : ' ');

        if(Object.keys(this.activeIcons).length > 0) {
            let iconData = [];

            // find unsynced icons
            this.getUnsyncedIcons().then(function(unsynced) {
                for(let i in _self.activeIcons) {
                    iconData.push(_self.activeIcons[i]);

                    // prevent duplicates
                    for(let k in unsynced) {
                        if(unsynced[k] == _self.activeIcons[i]) {
                            delete unsynced[k];
                        }
                    }

                    if(insertValue == 'svg') {
                        let iconValue = _self.activeIcons[i];

                        _self.sha1(iconValue).then(function (sha1Value) {
                            _self.widget.find(".fa-iconpicker-pager .fa-iconpicker-page button[data-svg-hash='" + sha1Value + "']").addClass("active");
                            $(_self.details).find("button[data-svg-hash='" + sha1Value + "']").addClass("active");
                        });
                    } else {
                        _self.widget.find(".fa-iconpicker-pager .fa-iconpicker-page button[data-"+ insertValue +"='" + i + "']").addClass("active");
                        $(_self.details).find("button[data-"+ insertValue +"='" + i + "']").addClass("active");
                    }
                }

                _self.target.val($.trim(iconData.join(delimiter) + (unsynced.length ? " " + unsynced.join(delimiter) : "")));
                callback();

                // add weight
                if(_self.config['add-weight'] && insertValue == 'name') {
                    _self.target.val('fa' + _self.config['preview-weight'].toLowerCase() +" "+ _self.target.val());
                }
            });
        } else {
            callback();
        }
    }

    /**
     * overlay on hover over icon button
     */
    showIconDetails(button) {
        const _self = this;

        // remove all details from elsewhere
        $(_self.details).remove();

        if(this.iconTooltipTimeout != null) {
            clearTimeout(this.iconTooltipTimeout);
            this.iconTooltipTimeout = null;
        }

        this.iconTooltipTimeout = setTimeout(function(){
            let cloneButton = button.clone(true, true);
            let isBrand = button.children("i").hasClass("fab");

            _self.details = $(
                '<div class="fa-iconpicker-icon-details">' +
                    '<div class="fa-iconpicker-icon-details-inner">' +
                        '<div class="fa-iconpicker-icon-details-name"><label>'+
                            FAPickerAddonI18N['fa_iconpicker_widget_icon_details_name'] +'</label><span>'+ button.attr("data-name") +'</span></div>' +
                        '<div class="fa-iconpicker-icon-details-search-code"><label>'+
                            FAPickerAddonI18N['fa_iconpicker_widget_icon_details_code'] +'</label><span>'+ button.attr("data-code") +'</span></div>' +
                        '<div class="fa-iconpicker-icon-details-search-label"><label>'+
                            FAPickerAddonI18N['fa_iconpicker_widget_icon_details_label'] +'</label><span>'+ button.attr("data-label") +'</span></div>' +
                        '<div class="fa-iconpicker-icon-details-search-terms '+ (button.attr("data-search-terms") == "" ? 'hidden' : '') +'"><label>'+
                            FAPickerAddonI18N['fa_iconpicker_widget_icon_details_searchterms'] +'</label><span>'+ button.attr("data-search-terms") +'</span></div>' +
                        '<div class="fa-iconpicker-icon-details-weights '+ (isBrand ? 'hidden' : '') +'"><label>'+
                            FAPickerAddonI18N['fa_iconpicker_widget_icon_details_weights'] +'</label><span></span></div>' +
                    '</div>' +
                '</div>');
            //  data-toggle="tooltip" data-placement="right" title="'+ FAPickerAddonI18N['fa_iconpicker_widget_move_tooltip'] +'"

            _self.details.appendTo($("body"));

            // add weights
            let weights = button.attr("data-weights").split(",");

            for(let i=0 ; i<weights.length ; i++) {
                $('<i class="fa'+ weights[i].toLowerCase() +' fa-'+ button.attr("data-name") +'" data-toggle="tooltip" data-placement="bottom" title="'+
                    FAPickerAddonI18N['fa_iconpicker_widget_weight_tooltip_' + weights[i]] +'"></i>')
                .appendTo(_self.details.find(".fa-iconpicker-icon-details-weights"));
            }

            _self.details.find('[data-toggle="tooltip"]').tooltip(_self.tooltipSettings);

            cloneButton.unbind("mouseenter mouseleave");
            cloneButton.children("i").removeClass("fa fat fal far fas fab").addClass("fa"+ _self.config['preview-weight'].toLowerCase());
            cloneButton.appendTo(_self.details);

            _self.details.css("left", button.offset().left - 11 + "px");
            _self.details.css("top", (button.offset().top + button.outerHeight() + 2) + "px"); //  + $("html").scrollTop()
            _self.details.mouseleave(function(e){
                $(this).remove();
                this.iconTooltipTimeout = null;
            });

            _self.details.animate({
                opacity: 1
            }, _self.fadeInSpeed);

        }, this.iconTooltipDelay);
    }

    /**
     * update latest used cookie & picker section
     * @param addedIcon latest added icon
     */
    updateLatestUsed(addedIcon = null) {
        const _self = this;

        let cookies = $.cookie(this.latestUsedCookieName);
        let icons = (cookies == undefined ? [] : JSON.parse(cookies));
        let latestUsed = this.widget.find(".fa-iconpicker-latest-wrapper");
        let previewWeight = this.config['preview-weight'];
        let activeIcons = this.activeIcons;

        latestUsed.html("");

        // no button > stop
        if(addedIcon != null) {
            let refButton = this.widget.find(".fa-iconpicker-pager .fa-iconpicker-page button[data-name='" + addedIcon + "']");

            // check for duplicates > remove older entries
            icons.forEach(function(value, key) {
                if(value['data-id'] == refButton.attr("data-id")) {
                    icons.splice(key, 1);
                }
            });

            // check stack limit
            if(icons.length == this.latestUsedLimit) {
                icons.shift();
            }

            let newIcon = {
                'weight' : previewWeight.toLowerCase()
            };

            ['id','name','code','label','svg-hash','search-terms','weights'].forEach(value => {
                newIcon['data-'+ value] = refButton.attr("data-"+ value);
            });

            icons.push(newIcon);
        }

        // save
        $.cookie(
            this.latestUsedCookieName,
            JSON.stringify(icons),
            { expires: 999, path: '/redaxo' }
        );

        icons = icons.reverse();

        // update latest used section
        icons.forEach(function(value, key) {
            let latestUsedButton = $('<button ' +
                'data-id="'+ value['data-id'] +'" ' +
                'data-name="'+ value['data-name'] +'" ' +
                'data-code="'+ value['data-code'] +'" ' +
                'data-label="'+ value['data-label'] +'" ' +
                'data-svg-hash="'+ value['data-svg-hash'] +'" ' +
                'data-search-terms="'+ value['data-search-terms'] +'" ' +
                'data-weights="'+ value['data-weights'] +'">' +
                    '<i class="fa'+ value['weight'] +' fa-'+ value['data-name'] +'"></i>' +
                '</button>')
            .appendTo(latestUsed)
            .addClass(activeIcons[value['data-name']] != undefined ? 'active' : '')
            .click(function(e){
                e.stopPropagation();

                let ret = true;

                // before select event
                if(_self.config.onbeforeselect) {
                    if (typeof (_self.config.onbeforeselect) == "function") {
                        ret = _self.config.onbeforeselect(this, _self.target[0]);
                    } else if (typeof (_self.config.onbeforeselect) == "string") {
                        ret = window[_self.config.onbeforeselect](this, _self.target[0]);
                    }
                }

                if(ret === false) {
                    return;
                }

                _self.selectIcon($(this));
            });

            // bind details
            if(_self.config['details-on-hover']) {
                latestUsedButton.mouseenter(function(e) {
                    _self.showIconDetails($(this));
                });

                latestUsedButton.mouseleave(function(e){
                    if(_self.iconTooltipTimeout != null) {
                        clearTimeout(_self.iconTooltipTimeout);
                        _self.iconTooltipTimeout = null;
                    }
                });
            }
        });
    }

    /**
     * get target input or textarea
     * @return {null}
     */
    get getTarget() {
        return this.target;
    }
}

/**
 * on load (including pjax)
 */
$(document).on('rex:ready', function (e, container) {
    // init picker
    $(".rex-fa-iconpicker").each(function(idx) {
        if(!$(this).is("input[type=text]") && !$(this).is("textarea")) {
            return;
        }

        new FAPicker(this);
    });

    // (re)init dropzone
    if($("#rex-page-fa-iconpicker-packages #fa-picker-upload").length > 0) {
        var element = $("#fa-picker-upload")[0];

        if (!element.dropzone) {
            new Dropzone("#fa-picker-upload");
        }
    }

    // init bootstrap tooltips in packages overview
    $('#rex-page-fa-iconpicker-packages .rex-page-main [data-toggle="tooltip"]').tooltip({
        html: true
    })

    // delete button confirm in packages overview
    $("#rex-page-fa-iconpicker-packages .rex-page-main .rex-fa-packages-wrapper button[name='delete']").click(function(e){
        return confirm(FAPickerAddonI18N["fa_iconpicker_action_delete_confirm"]);
    });
});

/**
 * drop zone config
 */
Dropzone.options.faPickerUpload = {
    timeout: 120000,
    maxFiles: 5,
    maxFilesize: 100,
    acceptedFiles: ".zip",
    // previewsContainer: "#previews",
    clickable: true,
    createImageThumbnails: false,
    autoProcessQueue: false,
    addRemoveLinks: true,

    // translations
    dictDefaultMessage: FAPickerAddonI18N["fa_iconpicker_dropzone_dictDefaultMessage"],
    dictFallbackMessage: FAPickerAddonI18N["fa_iconpicker_dropzone_dictFallbackMessage"], // Default: Your browser does not support drag'n'drop file uploads.
    dictFileTooBig: FAPickerAddonI18N["fa_iconpicker_dropzone_dictFileTooBig"], // Default: File is too big ({{filesize}}MiB). Max filesize: {{maxFilesize}}MiB.
    dictInvalidFileType: FAPickerAddonI18N["fa_iconpicker_dropzone_dictInvalidFileType"], // Default: You can't upload files of this type.
    dictResponseError: FAPickerAddonI18N["fa_iconpicker_dropzone_dictResponseError"], // Default: Server responded with {{statusCode}} code.
    dictCancelUpload: FAPickerAddonI18N["fa_iconpicker_dropzone_dictCancelUpload"], // Default: Cancel upload
    dictUploadCanceled: FAPickerAddonI18N["fa_iconpicker_dropzone_dictUploadCanceled"], // Default: Upload canceled.
    dictCancelUploadConfirmation: FAPickerAddonI18N["fa_iconpicker_dropzone_dictCancelUploadConfirmation"], // Default: Are you sure you want to cancel this upload?
    dictRemoveFile: FAPickerAddonI18N["fa_iconpicker_dropzone_dictRemoveFile"], // Default: Remove file
    dictRemoveFileConfirmation: null, // Default: null
    dictMaxFilesExceeded: FAPickerAddonI18N["fa_iconpicker_dropzone_dictMaxFilesExceeded"], // Default: You can not upload any more files.
    dictFileSizeUnits: {tb: "TB", gb: "GB", mb: "MB", kb: "KB", b: "b"},

    // events
    init: function() {
        let dropzone = $("#fa-picker-upload")[0].dropzone;

        $(".dropzone-actions").removeClass("hidden");

        $(".dropzone-actions").find("button#rex-fa5-start-upload").click(function(){
            dropzone.processQueue();
        });

        $(".dropzone-actions").find("button#rex-fa5-clear-queue").click(function(){
            dropzone.removeAllFiles(true);
        });
    },

    error: function(file, response) {
        if($.type(response) === "string") {
            var message = response; //dropzone sends it's own error messages in string
        } else {
            var message = response.message;
        }

        file.previewElement.classList.add("dz-error");
        _ref = file.previewElement.querySelectorAll("[data-dz-errormessage]");
        _results = [];

        for (_i = 0, _len = _ref.length; _i < _len; _i++) {
            node = _ref[_i];
            _results.push(node.textContent = message);
        }

        return _results;
    },

    completemultiple: function(files) {
        console.log(files);
    }
};

/*!
 * jQuery Cookie Plugin v1.3.1
 * https://github.com/carhartl/jquery-cookie
 *
 * Copyright 2013 Klaus Hartl
 * Released under the MIT license
 */
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as anonymous module.
        define(['jquery'], factory);
    } else {
        // Browser globals.
        factory(jQuery);
    }
}(function ($) {

    var pluses = /\+/g;

    function raw(s) {
        return s;
    }

    function decoded(s) {
        return decodeURIComponent(s.replace(pluses, ' '));
    }

    function converted(s) {
        if (s.indexOf('"') === 0) {
            // This is a quoted cookie as according to RFC2068, unescape
            s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
        }
        try {
            return config.json ? JSON.parse(s) : s;
        } catch(er) {}
    }

    var config = $.cookie = function (key, value, options) {

        // write
        if (value !== undefined) {
            options = $.extend({}, config.defaults, options);

            if (typeof options.expires === 'number') {
                var days = options.expires, t = options.expires = new Date();
                t.setDate(t.getDate() + days);
            }

            value = config.json ? JSON.stringify(value) : String(value);

            return (document.cookie = [
                config.raw ? key : encodeURIComponent(key),
                '=',
                config.raw ? value : encodeURIComponent(value),
                options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
                options.path    ? '; path=' + options.path : '',
                options.domain  ? '; domain=' + options.domain : '',
                options.secure  ? '; secure' : ''
            ].join(''));
        }

        // read
        var decode = config.raw ? raw : decoded;
        var cookies = document.cookie.split('; ');
        var result = key ? undefined : {};
        for (var i = 0, l = cookies.length; i < l; i++) {
            var parts = cookies[i].split('=');
            var name = decode(parts.shift());
            var cookie = decode(parts.join('='));

            if (key && key === name) {
                result = converted(cookie);
                break;
            }

            if (!key) {
                result[name] = converted(cookie);
            }
        }

        return result;
    };

    config.defaults = {};

    $.removeCookie = function (key, options) {
        if ($.cookie(key) !== undefined) {
            // Must not alter options, thus extending a fresh object...
            $.cookie(key, '', $.extend({}, options, { expires: -1 }));
            return true;
        }
        return false;
    };
}));
