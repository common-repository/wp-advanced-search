(function($) {
	$("input"+ac_param.selector).autocomplete({
		minLength: 1,
		source: function(request, response) {
			var acQuery = request.term;
			if(ac_param.multiple == true) {
				if(request.term.length > 0) {
					var acQuery = request.term.split(/ /).pop();
				}
			}

			$.ajax({
				type: "POST",
				url: ac_param.ajaxurl,
				data: {
					action: 'wpas_autocomplete',
					ac_query: acQuery,
				},
				success: function(data) {
					var results = $.ui.autocomplete.filter(JSON.parse(data), acQuery);
					if(ac_param.limitDisplay > 0) {
						response(results.slice(0, ac_param.limitDisplay));
					} else {
						response(results);
					}
				},
				error: function(errorThrown){
					console.log(errorThrown);
				}
			})
		},
		focus: function(event, ui) {
			return false;
		},
		select: function(event, ui) {
			if(ac_param.multiple == true) {
				var terms = this.value.split(/ /);
				terms.pop();
				terms.push(ui.item.value);
				terms.push("");
				this.value = terms.join(" ");
			} else {
				this.value = ui.item.value + " ";
			}
			return false;
        },
		autoFocus: ac_param.autoFocus
	});
})(jQuery);