(function(){
	
	var getUrl = window.location;
	var baseUrl = getUrl .protocol + "//" + getUrl.host + "/" + getUrl.pathname.split('/')[1];
	
	
	$('.bootstrapdatepicker').datetimepicker({
	initialDate : new Date(),
	startDate  : new Date(),
	autoclose : true,
	weekStart : 1,
	language : 'de',
	format : "dd.mm.yyyy",
	minView : 2
	
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
  valueNames: [ 'name','link','date' ]
})

new List('playerlist', {
  valueNames: [ 'name','sex','mail','number','balance' ]
})

new List('eventlist', {
  valueNames: [ 'name','location','date','status','count' ]
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
     buttonWidth : '100%',
     selectAllText : 'Alle auswählen',
     filterPlaceholder : 'Suche...',
     enableCaseInsensitiveFiltering : true,
     checkboxName: function(option) {
                return 'playerid[]';
            },
     buttonText: function(options, select) {
                if (options.length === 0) {
                    return 'Spieler*Innen auswählen';
                }
                else if (options.length > 10) {
                    return options.length + ' Spieler*Innen';
                }
                 else {
                     var labels = [];
                     options.each(function() {
                         if ($(this).attr('label') !== undefined) {
                             labels.push($(this).attr('label'));
                         }
                         else {
                             labels.push($(this).html());
                         }
                     });
                     return labels.join(', ') + '';
                 }
            },

 });

})();