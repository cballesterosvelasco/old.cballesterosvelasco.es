function filterByLabel(label, form) {
	if (label === undefined || label == '') {
		$('div.section').show(form);
		$('div.entry').show(form);
		$('.filterResults').hide('slow');
		$('#info').show('slow');
		
		//$('tr td.contentColumn').show(form);
	} else {
		$('.filterResults').show('slow');
		$('#info').hide('slow');
		var selector = "ul.labels li[data-tag-name='" + label + "']";
		//alert(selector);
		$('div.section').each(function(k, element) {
			var containLabel = ($(element).find(selector).length > 0);
			if (containLabel) {
				$(element).show(form);
			} else {
				$(element).hide(form);
			}
			$('div.entry').each(function(k, element) {
				//alert(element);
				var containLabel = ($(element).find(selector).length > 0);
				if (containLabel) {
					$(element).show(form);
					//$(element).find('td.contentColumn').show(form);
				} else {
					$(element).hide(form);
					//$(element).find('td.contentColumn').hide(form);
				}
			});
		});
	}
}

function setHash(hash) {
	if (history.pushState) {
		history.pushState('', document.title, window.location.pathname);
	}
    window.location.hash = hash;
}

function onHashChanged() {
	var label = String(document.location.hash).replace(/^#/, '');
	//alert( location.hash );
	//filterByLabel(label, 'fast');
	if (label.indexOf('section_') !== 0) {
		filterByLabel(label);
	}
}

$(document).on('ready', function () {
	var label = String(document.location.hash).replace(/^#/, '');
	$('.label').on('click', function () {
		var label = $(this).data('tag-name');
		if (label === undefined) label = '';
		setHash(label);
		onHashChanged();
	});
	if (label.indexOf('section_') !== 0) {
		setHash(document.location.hash);
		$('.filterResults').hide();
		if (document.location.hash) {
			onHashChanged();
			$(document.body).css('display', 'block');
		}
	} else {
		$('.filterResults').hide();
		$(document.body).css('display', 'block');
	}
});

$(function () {
	$('#left_side').
		bind('mousewheel wheel', function (e) {
			//bind('mousewheel', function (e) {
			//console.log(e);
			var delta = e.wheelDelta || e.originalEvent.wheelDelta || -e.originalEvent.deltaY || e.detail;
			//console.log(delta);
			var sign = (delta < 0) ? -1 : +1;
			if (Math.abs(delta) < 64) delta = 64 * sign;
			//console.log(sign + ":" + delta);
			//$(this).animate({ 'scrollTop': '-=' + delta }, { duration: 200 });
			this.scrollTop -= delta;
			e.preventDefault();
		})
	;

	$("a.fancybox-thumb").fancybox({
		prevEffect: 'none',
		nextEffect: 'none',
		helpers: {
			title: {
				type: 'outside'
			},
			thumbs: {
				width: 50,
				height: 50
			}
		}
	});
});

$(window).on('hashchange', onHashChanged);

if (document.location.hash) {
	$(document.body).css('display', 'none');
}
