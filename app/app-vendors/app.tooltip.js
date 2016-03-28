(function($){
	$('.icons-item').on('click mouseover', function() {
			$('.icons-item').removeClass('icons-item--touched');
			var $this = $(this);
			var $tag = $this.find('.icons-tag');
			var $pos = $this.position();
			var $height = $this.height();
			$this.addClass('icons-item--touched')
			$tag.css({top: $pos.top + $height, left: $pos.left});
			console.log('oki');
		});
})(jQuery);