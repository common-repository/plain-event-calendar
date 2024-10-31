var el = wp.element.createElement,
	registerBlockType = wp.blocks.registerBlockType,
	ServerSideRender = wp.components.ServerSideRender,
	TextControl = wp.components.TextControl,
	RadioControl = wp.components.RadioControl,
	SelectControl = wp.components.SelectControl,
	TextareaControl = wp.components.TextareaControl,
	CheckboxControl = wp.components.CheckboxControl,
	InspectorControls = wp.editor.InspectorControls;

registerBlockType('plain-event-calendar/plain-event-calendar', {
	title: 'Plain Event Calendar',
	description: 'Displays the list of events',
	icon: 'list-view',
	category: 'widgets',
	edit: function (props) {
		return [
			el( 'h2',
				{
					className: props.className,
				},
				'Plain Event Calendar' // Block content
			),
            // el(TextControl, {
                // label: 'ID',
                // value: props.attributes.id,
                // onChange: (value) => {
                    // props.setAttributes({id: value});
                // },
            // }),
        ];
    },
	save: function () {
		return null;
	},
});