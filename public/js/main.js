(function() {

    $('.bootstrapdatepicker').datetimepicker({
        initialDate: new Date(),
        startDate: new Date(),
        autoclose: true,
        weekStart: 1,
        language: 'de',
        format: "dd.mm.yyyy",
        minView: 2
    });
    var numbers = new Bloodhound({
        datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        prefetch: jsbase + 'player/json',
        remote: {
            url: jsbase + 'player/json?q=%QUERY',
            wildcard: '%QUERY'
        }
    });
    numbers.initialize();
    $(".playerselect").typeahead({
        items: 4,
        fitToElement: true,
        source: numbers.ttAdapter()
    });
    new List('linklist', {
        valueNames: ['name', 'link', 'date']
    })
    new List('playerlist', {
        valueNames: ['name', 'sex', 'mail', 'number', 'balance']
    })
    new List('eventlist', {
        valueNames: ['name', 'location', 'date', 'status', 'count']
    })
    $('[data-toggle="popover"]').popover();
    $('#summernote').summernote({
        height: 520,
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['font', ['strikethrough', 'superscript', 'subscript']],
            ['fontsize', ['fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']]
        ]
    });
    $('#messagereceiver').multiselect({
        enableFiltering: true,
        includeSelectAllOption: true,
        maxHeight: 400,
        buttonWidth: '100%',
        selectAllText: 'Alle auswählen',
        filterPlaceholder: 'Suche...',
        enableCaseInsensitiveFiltering: true,
        checkboxName: function(option) {
            return 'playerid[]';
        },
        buttonText: function(options, select) {
            if (options.length === 0) {
                return 'Spieler*Innen auswählen';
            } else if (options.length > 10) {
                return options.length + ' Spieler*Innen';
            } else {
                var labels = [];
                options.each(function() {
                    if ($(this).attr('label') !== undefined) {
                        labels.push($(this).attr('label'));
                    } else {
                        labels.push($(this).html());
                    }
                });
                return labels.join(', ') + '';
            }
        },
        onDropdownShown: function(event) {
            this.$select.parent().find("button.multiselect-clear-filter").click();
            this.$select.parent().find("input[type='text'].multiselect-search").focus();
        }
    });

    $('#messagesender').multiselect({
        enableFiltering: true,
        maxHeight: 400,
        buttonWidth: '100%',
        selectAllText: 'Alle auswählen',
        filterPlaceholder: 'Suche...',
        enableCaseInsensitiveFiltering: true,
        onDropdownShown: function(event) {
            this.$select.parent().find("button.multiselect-clear-filter").click();
            this.$select.parent().find("input[type='text'].multiselect-search").focus();
        }
    });



    $('#eventcalendar').calendar({
        language : 'de',
        dataSource : function(){
            
            var el = $('tr.eventdata');

            return $.map(el,function(o,i){
                o = $(o);
                return {
                    id : o.data('id'),
                    name : o.data('title'),
                    location : o.data('location'),
                    startDate: new Date(o.data('start')),
                    endDate: new Date(o.data('end'))
                }
            });
        }(),
        mouseOnDay: function(e) {
            if(e.events.length > 0) {
                var content = '';
                
                for(var i in e.events) {
                    content += '<div class="event-tooltip-content">'
                                    + '<div class="event-name" style="color:' + e.events[i].color + '">' + e.events[i].name + '</div>'
                                    + '<div class="event-location">' + e.events[i].location + '</div>'
                                + '</div>';
                }
            
                $(e.element).popover({ 
                    trigger: 'manual',
                    container: 'body',
                    html:true,
                    content: content
                });
                
                $(e.element).popover('show');
            }
        },
        mouseOutDay: function(e) {
            if(e.events.length > 0) {
                $(e.element).popover('hide');
            }
        },
        clickDay : function(e){

            if(e.events.length>0){
                    window.location = jsbase + 'event/' + e.events[0].id
            }

        }
    })
    
})();