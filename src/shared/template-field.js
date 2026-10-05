import { TextControl, TextareaControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const VARIABLE_LABELS = {
	title: __( 'Title', 'moon-seo' ),
	term_title: __( 'Term Title', 'moon-seo' ),
	site_name: __( 'Site Name', 'moon-seo' ),
	tagline: __( 'Tagline', 'moon-seo' ),
	separator: __( 'Separator', 'moon-seo' ),
	query: __( 'Search Query', 'moon-seo' ),
};

function getVariableLabel( variable ) {
	if ( VARIABLE_LABELS[ variable ] ) {
		return VARIABLE_LABELS[ variable ];
	}

	// Fallback: "custom_field" becomes "Custom Field".
	return variable
		.split( '_' )
		.map( ( word ) => word.charAt( 0 ).toUpperCase() + word.slice( 1 ) )
		.join( ' ' );
}

// Text field with buttons that append {variable} tokens to the end of the value.
export default function TemplateField( {
	label,
	help,
	value,
	onChange,
	variables,
	maxLength,
	multiline,
	placeholder,
} ) {
	const currentValue = value || '';

	const insertVariable = ( variable ) => {
		onChange( `${ currentValue }{${ variable }}` );
	};

	const Control = multiline ? TextareaControl : TextControl;

	return (
		<div className="moon-seo-template-field">
			{ variables && variables.length > 0 && (
				<div className="moon-seo-template-field__variables">
					{ variables.map( ( variable ) => (
						<Button
							key={ variable }
							className="moon-seo-template-field__variable"
							onClick={ () => insertVariable( variable ) }
							title={ `{${ variable }}` }
						>
							{ getVariableLabel( variable ) }
						</Button>
					) ) }
				</div>
			) }

			<Control
				__nextHasNoMarginBottom
				{ ...( multiline ? {} : { __next40pxDefaultSize: true } ) }
				label={ label }
				help={ help }
				value={ currentValue }
				onChange={ onChange }
				placeholder={ placeholder }
			/>

			{ maxLength && (
				<p className="moon-seo-field__counter" aria-live="polite">
					{ currentValue.length } / { maxLength }
				</p>
			) }
		</div>
	);
}