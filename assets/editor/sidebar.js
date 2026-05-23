/* global wp */
( function () {
	'use strict';

	if ( ! wp || ! wp.plugins || ! wp.element ) {
		return;
	}

	var el                = wp.element.createElement;
	var Fragment          = wp.element.Fragment;
	var useState          = wp.element.useState;

	// Sidebar exports moved from edit-post to editor in recent WP versions; fall back gracefully.
	var PluginSidebar     = ( wp.editor && wp.editor.PluginSidebar )              || ( wp.editPost && wp.editPost.PluginSidebar );
	var PluginSidebarMore = ( wp.editor && wp.editor.PluginSidebarMoreMenuItem )   || ( wp.editPost && wp.editPost.PluginSidebarMoreMenuItem );
	if ( ! PluginSidebar || ! PluginSidebarMore ) {
		return;
	}

	var Button            = wp.components.Button;
	var TextareaControl   = wp.components.TextareaControl;
	var PanelBody         = wp.components.PanelBody;
	var Spinner           = wp.components.Spinner;
	var useSelect         = wp.data.useSelect;
	var apiFetch          = wp.apiFetch;
	var __                = wp.i18n.__;

	function CuraiSidebar() {
		var postId = useSelect( function ( select ) {
			return select( 'core/editor' ).getCurrentPostId();
		}, [] );

		var busyState   = useState( false );
		var outputState = useState( '' );
		var setBusy     = busyState[1];
		var setOutput   = outputState[1];
		var busy        = busyState[0];
		var output      = outputState[0];

		function run( abilityId, body ) {
			if ( ! postId ) {
				setOutput( __( 'Save the post first.', 'curator-ai-seo-site-care' ) );
				return;
			}
			setBusy( true );
			setOutput( '' );
			apiFetch( {
				path: '/curator-ai/v1/abilities/' + encodeURIComponent( abilityId ) + '/run',
				method: 'POST',
				data: Object.assign( { post_id: postId }, body || {} )
			} ).then( function ( res ) {
				setBusy( false );
				setOutput( typeof res === 'string' ? res : JSON.stringify( res, null, 2 ) );
			} ).catch( function ( err ) {
				setBusy( false );
				var msg = ( err && err.message ) ? err.message : JSON.stringify( err );
				setOutput( __( 'Error: ', 'curator-ai-seo-site-care' ) + msg );
			} );
		}

		return el(
			Fragment,
			null,
			el( PluginSidebarMore, {
				target: 'curator-ai-sidebar',
				icon: 'superhero'
			}, __( 'Curator AI', 'curator-ai-seo-site-care' ) ),

			el( PluginSidebar, {
				name: 'curator-ai-sidebar',
				title: __( 'Curator AI', 'curator-ai-seo-site-care' ),
				icon: 'superhero'
			},
				el( 'div', { className: 'curai-sidebar' },

					el( PanelBody, { title: __( 'SEO', 'curator-ai-seo-site-care' ), initialOpen: true },
						el( Button, {
							variant: 'primary',
							isBusy: busy,
							disabled: busy,
							onClick: function () { run( 'curator-ai/generate-meta-title' ); }
						}, __( 'Generate Meta Title', 'curator-ai-seo-site-care' ) ),
						' ',
						el( Button, {
							variant: 'secondary',
							isBusy: busy,
							disabled: busy,
							onClick: function () { run( 'curator-ai/generate-meta-description' ); }
						}, __( 'Generate Meta Description', 'curator-ai-seo-site-care' ) )
					),

					el( PanelBody, { title: __( 'Readability', 'curator-ai-seo-site-care' ), initialOpen: false },
						el( Button, {
							variant: 'secondary',
							isBusy: busy,
							disabled: busy,
							onClick: function () { run( 'curator-ai/audit-readability' ); }
						}, __( 'Score Readability', 'curator-ai-seo-site-care' ) )
					),

					el( PanelBody, { title: __( 'Content Freshness', 'curator-ai-seo-site-care' ), initialOpen: false },
						el( Button, {
							variant: 'secondary',
							isBusy: busy,
							disabled: busy,
							onClick: function () { run( 'curator-ai/refresh-content', { mode: 'date_only' } ); }
						}, __( 'Refresh Date Only', 'curator-ai-seo-site-care' ) ),
						' ',
						el( Button, {
							variant: 'secondary',
							isBusy: busy,
							disabled: busy,
							onClick: function () { run( 'curator-ai/refresh-content', { mode: 'context' } ); }
						}, __( 'Refresh with Context', 'curator-ai-seo-site-care' ) )
					),

					el( PanelBody, { title: __( 'Broken Links', 'curator-ai-seo-site-care' ), initialOpen: false },
						el( Button, {
							variant: 'secondary',
							isBusy: busy,
							disabled: busy,
							onClick: function () { run( 'curator-ai/audit-broken-links' ); }
						}, __( 'Scan Post Links', 'curator-ai-seo-site-care' ) )
					),

					busy ? el( 'div', { className: 'curai-sidebar-busy' }, el( Spinner ) ) : null,

					output ? el( PanelBody, { title: __( 'Output', 'curator-ai-seo-site-care' ), initialOpen: true },
						el( TextareaControl, {
							value: output,
							readOnly: true,
							rows: 10,
							onChange: function () { /* readonly */ }
						} )
					) : null
				)
			)
		);
	}

	wp.plugins.registerPlugin( 'curator-ai-sidebar', { render: CuraiSidebar } );
} )();
