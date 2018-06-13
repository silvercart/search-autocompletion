
$(document).ready(function() {
    
    var currentQuickSearchRequest = false;
    
    $('body').append('<div class="silvercart-search-autocompletion-results"><ul></ul></div>');
    $('input[name="quickSearchQuery"]').attr('autocomplete','off');
    
    $('input[name="quickSearchQuery"]').keydown(function(event) {
        if (event.keyCode === 13) {
            // enter
            if ($('.silvercart-search-autocompletion-results ul li.active').length) {
                event.preventDefault();
            }
        }
    });
    
    $('input[name="quickSearchQuery"]').keyup(function(event) {
        
        var inputField          = $(this),
            inputFieldOffset    = inputField.offset(),
            searchTerm          = inputField.val(),
            autoCompleteList    = $('.silvercart-search-autocompletion-results ul'),
            uri                 = document.baseURI ? document.baseURI : '/';
        
        autoCompleteList.css('top', inputFieldOffset.top + inputField.outerHeight());
        autoCompleteList.css('left', inputFieldOffset.left);
        
        if (event.keyCode === 40) {
            // down
            var rel = 0;
            if ($('.silvercart-search-autocompletion-results ul li.active').length) {
                rel = $('.silvercart-search-autocompletion-results ul li.active').attr('rel');
                rel = parseInt(rel) + 1;
            }
            var li  = $('.silvercart-search-autocompletion-results ul li[rel="' + rel + '"]').addClass('active');
            if (li.length) {
                $('.silvercart-search-autocompletion-results ul li.active').removeClass('active');
                li.addClass('active');
            }
        } else if (event.keyCode === 38) {
            // up
            if ($('.silvercart-search-autocompletion-results ul li.active').length) {
                var rel = $('.silvercart-search-autocompletion-results ul li.active').attr('rel');
                rel = parseInt(rel) - 1;
                var li  = $('.silvercart-search-autocompletion-results ul li[rel="' + rel + '"]').addClass('active');
                if (li.length) {
                    $('.silvercart-search-autocompletion-results ul li.active').removeClass('active');
                    li.addClass('active');
                }
            }
        } else if (event.keyCode === 13) {
            // enter
            if ($('.silvercart-search-autocompletion-results ul li.active').length) {
                event.preventDefault();
                document.location.href = $('.silvercart-search-autocompletion-results ul li.active a').attr('href');
            }
        } else if (searchTerm === '' || searchTerm.length < 3) {
            autoCompleteList.html('');
        } else {
            if (currentQuickSearchRequest !== false) {
                currentQuickSearchRequest.abort();
            }
            currentQuickSearchRequest = $.ajax({
                url:        uri + SCSA_MODULE_NAME + '/results.php?locale=' + SSALOCALE + '&pt=' + SSAPT,
                dataType:   'json',
                async:      true,
                type:       'POST',
                data: {
                    searchTerm: searchTerm
                },
                success:    function(data) {
                    var currentindex = 0;
                    autoCompleteList.html('');
                    $.each(data, function() {
                        if (this.Title) {
                            var displayTitle = this.Title.replace(searchTerm, '<strong>' + searchTerm + '</strong>');
                            displayTitle = displayTitle.replace(searchTerm.toUpperCase(), '<strong>' + searchTerm.toUpperCase() + '</strong>');
                            displayTitle = displayTitle.replace(searchTerm.charAt(0).toUpperCase() + searchTerm.slice(1), '<strong>' + searchTerm.charAt(0).toUpperCase() + searchTerm.slice(1) + '</strong>');

                            var displayProductNumberShop = this.ProductNumberShop.replace(searchTerm, '<strong>' + searchTerm + '</strong>');
                            displayProductNumberShop = displayProductNumberShop.replace(searchTerm.toUpperCase(), '<strong>' + searchTerm.toUpperCase() + '</strong>');
                            displayProductNumberShop = displayProductNumberShop.replace(searchTerm.charAt(0).toUpperCase() + searchTerm.slice(1), '<strong>' + searchTerm.charAt(0).toUpperCase() + searchTerm.slice(1) + '</strong>');


                            if (typeof this.PriceNice === 'undefined') {
                                this.PriceNice = this.Price + ' ' + this.Currency;
                            }
                            autoCompleteList.append('<li rel="' + currentindex + '"><a href="' + uri + 'ssa/gotoresult/' + this.ID + '" class="clearfix"><span class="product-number">' + displayProductNumberShop + '</span><span class="title">' + displayTitle + '</span><span class="price">' + this.PriceNice + '</span></a></li>');
                            currentindex++;
                        }
                    });
                }
            });
        }
            
    });
    
    var autocompletionSelector = '.silvercart-search-autocompletion-results ul li a';
    if (typeof $(autocompletionSelector).live === 'function') {
        $(autocompletionSelector).live('hover', function() {
            $('.silvercart-search-autocompletion-results ul li.active').removeClass('active');
        });
    } else if (typeof $(autocompletionSelector).on === 'function') {
        $(autocompletionSelector).on('hover', function() {
            $('.silvercart-search-autocompletion-results ul li.active').removeClass('active');
        });
    }
    
});