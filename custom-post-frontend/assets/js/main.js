( function () {
	'use strict';

	var cpfActiveModal = null;

	/**
	 * Get localized config with safe defaults.
	 *
	 * @return {Object} Configuration object.
	 */
	function cpfGetConfig() {
		var defaults = {
			invalidVideoMessage: 'The selected video URL is invalid or unavailable.',
			noGalleryMessage: 'No gallery images are available for this item.'
		};

		if ( 'object' !== typeof window.cpfFrontendData || ! window.cpfFrontendData ) {
			return defaults;
		}

		if ( 'string' !== typeof window.cpfFrontendData.invalidVideoMessage ) {
			window.cpfFrontendData.invalidVideoMessage = defaults.invalidVideoMessage;
		}

		if ( 'string' !== typeof window.cpfFrontendData.noGalleryMessage ) {
			window.cpfFrontendData.noGalleryMessage = defaults.noGalleryMessage;
		}

		return window.cpfFrontendData;
	}

	var cpfConfig = cpfGetConfig();

	/**
	 * Initialize all plugin frontend interactions.
	 *
	 * @return {void}
	 */
	function cpfInit() {
		var displays = document.querySelectorAll( '.cpf-display' );
		var index = 0;

		for ( index = 0; index < displays.length; index += 1 ) {
			cpfInitTabs( displays[ index ] );
		}

		cpfRegisterAllModals();
		cpfInitPortfolioTriggers();
		cpfInitMovieTriggers();
	}

	/**
	 * Initialize tabs in a display wrapper.
	 *
	 * @param {HTMLElement} display Display wrapper.
	 * @return {void}
	 */
	function cpfInitTabs( display ) {
		var tabs = display.querySelectorAll( '.cpf-tab' );
		var panels = display.querySelectorAll( '.cpf-tab-panel' );

		if ( ! tabs.length || ! panels.length ) {
			return;
		}

		function activateTab( targetTab, shouldFocus ) {
			var targetPanelId = targetTab.getAttribute( 'data-cpf-target' );
			var targetPanel = targetPanelId ? document.getElementById( targetPanelId ) : null;
			var tabIndex = 0;
			var panelIndex = 0;

			if ( ! targetPanel ) {
				return;
			}

			for ( tabIndex = 0; tabIndex < tabs.length; tabIndex += 1 ) {
				tabs[ tabIndex ].classList.remove( 'is-active' );
				tabs[ tabIndex ].setAttribute( 'aria-selected', 'false' );
				tabs[ tabIndex ].setAttribute( 'tabindex', '-1' );
			}

			for ( panelIndex = 0; panelIndex < panels.length; panelIndex += 1 ) {
				panels[ panelIndex ].classList.remove( 'is-active' );
				panels[ panelIndex ].setAttribute( 'hidden', '' );
			}

			targetTab.classList.add( 'is-active' );
			targetTab.setAttribute( 'aria-selected', 'true' );
			targetTab.setAttribute( 'tabindex', '0' );

			targetPanel.classList.add( 'is-active' );
			targetPanel.removeAttribute( 'hidden' );

			if ( shouldFocus ) {
				targetTab.focus();
			}
		}

		function onTabKeydown( event, currentIndex ) {
			var key = event.key;
			var nextIndex = currentIndex;

			if ( 'ArrowRight' === key ) {
				nextIndex = currentIndex + 1 >= tabs.length ? 0 : currentIndex + 1;
			} else if ( 'ArrowLeft' === key ) {
				nextIndex = currentIndex - 1 < 0 ? tabs.length - 1 : currentIndex - 1;
			} else if ( 'Home' === key ) {
				nextIndex = 0;
			} else if ( 'End' === key ) {
				nextIndex = tabs.length - 1;
			} else {
				return;
			}

			event.preventDefault();
			activateTab( tabs[ nextIndex ], true );
		}

		var tabIndex = 0;
		for ( tabIndex = 0; tabIndex < tabs.length; tabIndex += 1 ) {
			( function ( index ) {
				tabs[ index ].addEventListener( 'click', function () {
					activateTab( tabs[ index ], false );
				} );

				tabs[ index ].addEventListener( 'keydown', function ( event ) {
					onTabKeydown( event, index );
				} );
			}( tabIndex ) );
		}

		activateTab( tabs[ 0 ], false );
	}

	/**
	 * Register click handlers for all modals.
	 *
	 * @return {void}
	 */
	function cpfRegisterAllModals() {
		var modals = document.querySelectorAll( '.cpf-modal' );
		var index = 0;

		for ( index = 0; index < modals.length; index += 1 ) {
			cpfRegisterModal( modals[ index ] );
		}
	}

	/**
	 * Register a single modal.
	 *
	 * @param {HTMLElement} modal Modal element.
	 * @return {void}
	 */
	function cpfRegisterModal( modal ) {
		if ( '1' === modal.getAttribute( 'data-cpf-bound' ) ) {
			return;
		}

		modal.setAttribute( 'data-cpf-bound', '1' );

		modal.addEventListener( 'click', function ( event ) {
			var closeTarget = event.target.closest( '[data-cpf-modal-close]' );
			if ( closeTarget ) {
				cpfCloseModal( modal );
			}
		} );
	}

	/**
	 * Open a modal by ID.
	 *
	 * @param {string} modalId Modal ID.
	 * @return {HTMLElement|null} Modal element.
	 */
	function cpfOpenModal( modalId ) {
		var modal = document.getElementById( modalId );
		var dialog = null;

		if ( ! modal ) {
			return null;
		}

		if ( cpfActiveModal && cpfActiveModal !== modal ) {
			cpfCloseModal( cpfActiveModal );
		}

		dialog = modal.querySelector( '.cpf-modal-dialog' );
		if ( ! dialog ) {
			return null;
		}

		if ( modal.cpfClosingTimeout ) {
			window.clearTimeout( modal.cpfClosingTimeout );
			modal.cpfClosingTimeout = null;
		}

		modal.cpfLastFocused = document.activeElement instanceof HTMLElement ? document.activeElement : null;
		modal.hidden = false;
		modal.setAttribute( 'aria-hidden', 'false' );
		modal.classList.add( 'is-open' );
		document.body.classList.add( 'cpf-modal-open' );

		modal.cpfKeyHandler = function ( event ) {
			if ( 'Escape' === event.key ) {
				event.preventDefault();
				cpfCloseModal( modal );
				return;
			}

			if ( 'Tab' === event.key ) {
				cpfTrapFocus( event, dialog );
			}
		};

		document.addEventListener( 'keydown', modal.cpfKeyHandler );

		window.requestAnimationFrame( function () {
			modal.classList.add( 'is-visible' );
			dialog.focus();
		} );

		cpfActiveModal = modal;
		return modal;
	}

	/**
	 * Close a modal element.
	 *
	 * @param {HTMLElement} modal Modal element.
	 * @return {void}
	 */
	function cpfCloseModal( modal ) {
		if ( ! modal ) {
			return;
		}

		if ( modal.cpfKeyHandler ) {
			document.removeEventListener( 'keydown', modal.cpfKeyHandler );
			modal.cpfKeyHandler = null;
		}

		modal.classList.remove( 'is-visible' );
		modal.setAttribute( 'aria-hidden', 'true' );

		modal.cpfClosingTimeout = window.setTimeout( function () {
			modal.hidden = true;
			modal.classList.remove( 'is-open' );
		}, 160 );

		if ( modal.cpfLastFocused && 'function' === typeof modal.cpfLastFocused.focus ) {
			modal.cpfLastFocused.focus();
		}

		if ( cpfActiveModal === modal ) {
			cpfActiveModal = null;
		}

		document.body.classList.remove( 'cpf-modal-open' );
		cpfDispatchModalEvent( modal, 'cpf:modalClosed' );
	}

	/**
	 * Dispatch a custom modal event.
	 *
	 * @param {HTMLElement} modal Modal element.
	 * @param {string} eventName Event name.
	 * @return {void}
	 */
	function cpfDispatchModalEvent( modal, eventName ) {
		var customEvent = null;

		if ( 'function' === typeof window.CustomEvent ) {
			customEvent = new window.CustomEvent( eventName, { bubbles: true } );
			modal.dispatchEvent( customEvent );
			return;
		}

		customEvent = document.createEvent( 'CustomEvent' );
		customEvent.initCustomEvent( eventName, true, false, {} );
		modal.dispatchEvent( customEvent );
	}

	/**
	 * Keep keyboard focus trapped within an open modal.
	 *
	 * @param {KeyboardEvent} event  Keyboard event.
	 * @param {HTMLElement}   dialog Dialog element.
	 * @return {void}
	 */
	function cpfTrapFocus( event, dialog ) {
		var focusableElements = cpfGetFocusableElements( dialog );
		var firstElement = null;
		var lastElement = null;

		if ( ! focusableElements.length ) {
			event.preventDefault();
			dialog.focus();
			return;
		}

		firstElement = focusableElements[ 0 ];
		lastElement = focusableElements[ focusableElements.length - 1 ];

		if ( event.shiftKey ) {
			if ( document.activeElement === firstElement || document.activeElement === dialog ) {
				event.preventDefault();
				lastElement.focus();
			}
			return;
		}

		if ( document.activeElement === lastElement ) {
			event.preventDefault();
			firstElement.focus();
		}
	}

	/**
	 * Get focusable elements inside an element.
	 *
	 * @param {HTMLElement} container Container element.
	 * @return {NodeListOf<HTMLElement>} Focusable elements.
	 */
	function cpfGetFocusableElements( container ) {
		return container.querySelectorAll(
			'a[href], button:not([disabled]), textarea:not([disabled]), input:not([type="hidden"]):not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
		);
	}

	/**
	 * Initialize portfolio card click handlers.
	 *
	 * @return {void}
	 */
	function cpfInitPortfolioTriggers() {
		var triggers = document.querySelectorAll( '.cpf-portfolio-trigger' );
		var index = 0;

		for ( index = 0; index < triggers.length; index += 1 ) {
			( function ( trigger ) {
				trigger.addEventListener( 'click', function () {
					var images = cpfParseImages( trigger.getAttribute( 'data-cpf-images' ) );
					var title = trigger.getAttribute( 'data-cpf-title' ) || '';

					cpfOpenPortfolioLightbox( images, title );
				} );
			}( triggers[ index ] ) );
		}
	}

	/**
	 * Parse image payload from data attribute.
	 *
	 * @param {string} imagesJson JSON string.
	 * @return {Array} Parsed image array.
	 */
	function cpfParseImages( imagesJson ) {
		var parsedImages = [];
		var cleanImages = [];
		var index = 0;

		try {
			parsedImages = JSON.parse( imagesJson || '[]' );
		} catch ( error ) {
			parsedImages = [];
		}

		if ( ! Array.isArray( parsedImages ) ) {
			return cleanImages;
		}

		for ( index = 0; index < parsedImages.length; index += 1 ) {
			if ( 'string' === typeof parsedImages[ index ] && '' !== parsedImages[ index ] ) {
				cleanImages.push(
					{
						url: parsedImages[ index ],
						alt: ''
					}
				);
				continue;
			}

			if ( ! parsedImages[ index ] || 'string' !== typeof parsedImages[ index ].url || '' === parsedImages[ index ].url ) {
				continue;
			}

			cleanImages.push(
				{
					url: parsedImages[ index ].url,
					alt: 'string' === typeof parsedImages[ index ].alt ? parsedImages[ index ].alt : ''
				}
			);
		}

		return cleanImages;
	}

	/**
	 * Open a Fancybox lightbox for portfolio images.
	 *
	 * @param {Array}  images Images array.
	 * @param {string} title  Gallery title.
	 * @return {void}
	 */
	function cpfOpenPortfolioLightbox( images, title ) {
		var items = [];
		var index = 0;

		if ( ! images.length ) {
			return;
		}

		for ( index = 0; index < images.length; index += 1 ) {
			if ( ! images[ index ] || 'string' !== typeof images[ index ].url || '' === images[ index ].url ) {
				continue;
			}

			items.push(
				{
					src: images[ index ].url,
					thumb: images[ index ].url,
					type: 'image',
					caption: title
				}
			);
		}

		if ( ! items.length ) {
			return;
		}

		if ( window.Fancybox && 'function' === typeof window.Fancybox.show ) {
			window.Fancybox.show(
				items,
				{
					Thumbs: {
						type: 'classic'
					},
					Toolbar: true,
					dragToClose: true
				}
			);
			return;
		}

		window.open( items[ 0 ].src, '_blank', 'noopener,noreferrer' );
	}

	/**
	 * Apply unique class names for a portfolio slider instance.
	 *
	 * @param {HTMLElement} element Element node.
	 * @param {string}      key     Dataset key.
	 * @param {string}      value   Class name value.
	 * @return {void}
	 */
	function cpfSetUniqueClass( element, key, value ) {
		if ( ! element ) {
			return;
		}

		if ( element.dataset && element.dataset[ key ] ) {
			element.classList.remove( element.dataset[ key ] );
		}

		if ( value ) {
			element.classList.add( value );
			if ( element.dataset ) {
				element.dataset[ key ] = value;
			}
			return;
		}

		if ( element.dataset ) {
			element.dataset[ key ] = '';
		}
	}

	/**
	 * Build a sanitized unique suffix.
	 *
	 * @param {string} base Base value.
	 * @return {string} Sanitized value.
	 */
	function cpfBuildUniqueSuffix( base ) {
		return String( base || '' ).toLowerCase().replace( /[^a-z0-9_-]+/g, '-' );
	}

	/**
	 * Populate the portfolio modal with slider content.
	 *
	 * @param {HTMLElement} modal  Modal element.
	 * @param {string}      title  Modal title.
	 * @param {Array}       images Gallery images.
	 * @param {string}      postId Post ID.
	 * @return {void}
	 */
	function cpfPopulatePortfolioModal( modal, title, images, postId ) {
		var titleNode = modal.querySelector( '.cpf-modal-title' );
		var mainSlider = modal.querySelector( '.cpf-slider-main' );
		var thumbsSlider = modal.querySelector( '.cpf-slider-thumbs' );
		var mainWrapper = modal.querySelector( '.cpf-slider-main .swiper-wrapper' );
		var thumbsWrapper = modal.querySelector( '.cpf-slider-thumbs .swiper-wrapper' );
		var pagination = modal.querySelector( '.cpf-slider-main .swiper-pagination' );
		var nextButton = modal.querySelector( '.cpf-slider-next' );
		var prevButton = modal.querySelector( '.cpf-slider-prev' );
		var modalId = modal.getAttribute( 'id' ) || 'portfolio-modal';
		var uniqueSuffix = cpfBuildUniqueSuffix( modalId + '-' + ( postId || '0' ) );
		var mainUnique = 'cpf-slider-main-' + uniqueSuffix;
		var thumbsUnique = 'cpf-slider-thumbs-' + uniqueSuffix;
		var nextUnique = 'cpf-slider-next-' + uniqueSuffix;
		var prevUnique = 'cpf-slider-prev-' + uniqueSuffix;
		var mainSelector = '';
		var thumbsSelector = '';
		var nextSelector = '';
		var prevSelector = '';
		var index = 0;

		if ( titleNode ) {
			titleNode.textContent = title;
		}

		if ( ! mainSlider || ! thumbsSlider || ! mainWrapper || ! thumbsWrapper ) {
			return;
		}

		cpfSetUniqueClass( mainSlider, 'cpfMainUnique', mainUnique );
		cpfSetUniqueClass( thumbsSlider, 'cpfThumbsUnique', thumbsUnique );
		cpfSetUniqueClass( nextButton, 'cpfNextUnique', nextUnique );
		cpfSetUniqueClass( prevButton, 'cpfPrevUnique', prevUnique );

		mainSelector = '.' + mainUnique;
		thumbsSelector = '.' + thumbsUnique;
		nextSelector = '.' + nextUnique;
		prevSelector = '.' + prevUnique;

		if ( modal.cpfMainSwiper && 'function' === typeof modal.cpfMainSwiper.destroy ) {
			modal.cpfMainSwiper.destroy( true, true );
			modal.cpfMainSwiper = null;
		}

		if ( modal.cpfThumbSwiper && 'function' === typeof modal.cpfThumbSwiper.destroy ) {
			modal.cpfThumbSwiper.destroy( true, true );
			modal.cpfThumbSwiper = null;
		}

		mainWrapper.innerHTML = '';
		thumbsWrapper.innerHTML = '';

		if ( ! images.length ) {
			mainWrapper.appendChild( cpfCreateEmptySlide( cpfConfig.noGalleryMessage ) );
			thumbsSlider.classList.add( 'is-hidden' );

			if ( pagination ) {
				pagination.classList.add( 'is-hidden' );
			}

			if ( nextButton ) {
				nextButton.classList.add( 'is-hidden' );
			}

			if ( prevButton ) {
				prevButton.classList.add( 'is-hidden' );
			}

			return;
		}

		for ( index = 0; index < images.length; index += 1 ) {
			mainWrapper.appendChild( cpfCreateImageSlide( images[ index ], false ) );
			thumbsWrapper.appendChild( cpfCreateImageSlide( images[ index ], true ) );
		}

		if ( images.length > 1 ) {
			thumbsSlider.classList.remove( 'is-hidden' );

			if ( pagination ) {
				pagination.classList.remove( 'is-hidden' );
			}

			if ( nextButton ) {
				nextButton.classList.remove( 'is-hidden' );
			}

			if ( prevButton ) {
				prevButton.classList.remove( 'is-hidden' );
			}
		} else {
			thumbsSlider.classList.add( 'is-hidden' );

			if ( pagination ) {
				pagination.classList.add( 'is-hidden' );
			}

			if ( nextButton ) {
				nextButton.classList.add( 'is-hidden' );
			}

			if ( prevButton ) {
				prevButton.classList.add( 'is-hidden' );
			}
		}

		if ( 'function' !== typeof window.Swiper ) {
			return;
		}

		modal.cpfThumbSwiper = new window.Swiper(
			thumbsSelector,
			{
				slidesPerView: 5,
				spaceBetween: 10,
				freeMode: true,
				watchSlidesProgress: true,
				slideToClickedSlide: true,
				breakpoints: {
					0: {
						slidesPerView: 3
					},
					768: {
						slidesPerView: 5
					}
				}
			}
		);

		modal.cpfMainSwiper = new window.Swiper(
			mainSelector,
			{
				spaceBetween: 12,
				keyboard: {
					enabled: true,
					onlyInViewport: false
				},
				navigation: {
					nextEl: nextSelector,
					prevEl: prevSelector
				},
				pagination: {
					el: pagination,
					clickable: true
				},
				thumbs: {
					swiper: modal.cpfThumbSwiper
				}
			}
		);
	}

	/**
	 * Create an empty state slide.
	 *
	 * @param {string} message Empty message.
	 * @return {HTMLElement} Slide element.
	 */
	function cpfCreateEmptySlide( message ) {
		var slide = document.createElement( 'div' );
		var wrapper = document.createElement( 'div' );

		slide.className = 'swiper-slide cpf-empty-slide';
		wrapper.className = 'cpf-empty-message';
		wrapper.textContent = message;
		slide.appendChild( wrapper );

		return slide;
	}

	/**
	 * Create a swiper image slide.
	 *
	 * @param {Object}  imageData Image data.
	 * @param {boolean} isThumb   Whether the slide is a thumb.
	 * @return {HTMLElement} Slide element.
	 */
	function cpfCreateImageSlide( imageData, isThumb ) {
		var slide = document.createElement( 'div' );
		var image = document.createElement( 'img' );

		slide.className = 'swiper-slide' + ( isThumb ? ' cpf-thumb-slide' : '' );
		image.setAttribute( 'src', imageData.url );
		image.setAttribute( 'alt', imageData.alt || '' );
		image.setAttribute( 'loading', 'lazy' );
		image.setAttribute( 'decoding', 'async' );

		slide.appendChild( image );
		return slide;
	}

	/**
	 * Initialize movie card click handlers.
	 *
	 * @return {void}
	 */
	function cpfInitMovieTriggers() {
		var triggers = document.querySelectorAll( '.cpf-movie-trigger' );
		var modals = document.querySelectorAll( '.cpf-movie-modal' );
		var index = 0;

		for ( index = 0; index < triggers.length; index += 1 ) {
			( function ( trigger ) {
				trigger.addEventListener( 'click', function () {
					var modalId = trigger.getAttribute( 'data-cpf-modal-id' );
					var modal = modalId ? document.getElementById( modalId ) : null;

					if ( ! modal || ! modalId ) {
						return;
					}

					cpfPopulateMovieModal(
						modal,
						trigger.getAttribute( 'data-cpf-title' ) || '',
						trigger.getAttribute( 'data-cpf-video-url' ) || ''
					);
					cpfOpenModal( modalId );
				} );
			}( triggers[ index ] ) );
		}

		for ( index = 0; index < modals.length; index += 1 ) {
			if ( '1' === modals[ index ].getAttribute( 'data-cpf-movie-bound' ) ) {
				continue;
			}

			modals[ index ].setAttribute( 'data-cpf-movie-bound', '1' );
			modals[ index ].addEventListener( 'cpf:modalClosed', function ( event ) {
				cpfResetMovieModal( event.currentTarget );
			} );
		}
	}

	/**
	 * Populate movie modal content.
	 *
	 * @param {HTMLElement} modal    Modal element.
	 * @param {string}      title    Modal title.
	 * @param {string}      videoUrl Video URL.
	 * @return {void}
	 */
	function cpfPopulateMovieModal( modal, title, videoUrl ) {
		var titleNode = modal.querySelector( '.cpf-modal-title' );
		var videoContainer = modal.querySelector( '[data-cpf-video-container]' );
		var messageNode = modal.querySelector( '[data-cpf-video-message]' );
		var iframe = null;
		var iframeTitle = '';

		if ( titleNode ) {
			titleNode.textContent = title;
		}

		if ( ! videoContainer ) {
			return;
		}

		videoContainer.innerHTML = '';

		if ( messageNode ) {
			messageNode.textContent = '';
			messageNode.setAttribute( 'hidden', '' );
		}

		if ( ! cpfIsValidEmbedUrl( videoUrl ) ) {
			if ( messageNode ) {
				messageNode.textContent = cpfConfig.invalidVideoMessage;
				messageNode.removeAttribute( 'hidden' );
			}
			return;
		}

		iframe = document.createElement( 'iframe' );
		iframeTitle = title ? title + ' video' : 'YouTube video player';
		iframe.setAttribute( 'src', cpfBuildAutoplayUrl( videoUrl ) );
		iframe.setAttribute( 'title', iframeTitle );
		iframe.setAttribute( 'loading', 'lazy' );
		iframe.setAttribute( 'allow', 'autoplay; encrypted-media' );
		iframe.setAttribute( 'allowfullscreen', '' );
		iframe.setAttribute( 'referrerpolicy', 'strict-origin-when-cross-origin' );
		iframe.setAttribute( 'frameborder', '0' );
		videoContainer.appendChild( iframe );
	}

	/**
	 * Reset movie modal content on close.
	 *
	 * @param {HTMLElement} modal Modal element.
	 * @return {void}
	 */
	function cpfResetMovieModal( modal ) {
		var videoContainer = modal.querySelector( '[data-cpf-video-container]' );
		var messageNode = modal.querySelector( '[data-cpf-video-message]' );
		var iframe = null;

		if ( videoContainer ) {
			iframe = videoContainer.querySelector( 'iframe' );
			if ( iframe && iframe.getAttribute( 'src' ) ) {
				iframe.setAttribute( 'src', iframe.getAttribute( 'src' ) );
			}
			videoContainer.innerHTML = '';
		}

		if ( messageNode ) {
			messageNode.textContent = '';
			messageNode.setAttribute( 'hidden', '' );
		}
	}

	/**
	 * Verify allowed embed URL format.
	 *
	 * @param {string} url URL.
	 * @return {boolean} Is valid.
	 */
	function cpfIsValidEmbedUrl( url ) {
		var pattern = /^https:\/\/www\.youtube(?:-nocookie)?\.com\/embed\/[A-Za-z0-9_-]{11}(?:[?#].*)?$/;
		return pattern.test( url );
	}

	/**
	 * Append autoplay and related params to embed URL.
	 *
	 * @param {string} url Embed URL.
	 * @return {string} URL with query params.
	 */
	function cpfBuildAutoplayUrl( url ) {
		var parsedUrl = null;
		var separator = '';

		try {
			parsedUrl = new window.URL( url, window.location.origin );
			parsedUrl.searchParams.set( 'autoplay', '1' );
			parsedUrl.searchParams.set( 'modestbranding', '1' );
			parsedUrl.searchParams.set( 'rel', '0' );
			parsedUrl.searchParams.set( 'controls', '1' );
			parsedUrl.searchParams.set( 'showinfo', '0' );
			return parsedUrl.toString();
		} catch ( error ) {
			separator = -1 === url.indexOf( '?' ) ? '?' : '&';
			return url + separator + 'autoplay=1&modestbranding=1&rel=0&controls=1&showinfo=0';
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', cpfInit );
	} else {
		cpfInit();
	}
}() );
