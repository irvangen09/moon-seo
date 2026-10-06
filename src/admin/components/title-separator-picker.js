import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

// Must match ALLOWED_SEPARATORS in Settings/SiteInfo.php.
const SEPARATORS = [
	'|',
	'-',
	'—',
	':',
	'.',
	'•',
	'*',
	'~',
	'«',
	'»',
	'/',
	'\\',
	'>',
	'<',
];

// Screen readers announce these symbols inconsistently, so each button gets an explicit name.
const SEPARATOR_LABELS = {
	'|': __( 'Vertical bar', 'moon-seo' ),
	'-': __( 'Hyphen', 'moon-seo' ),
	'—': __( 'Em dash', 'moon-seo' ),
	':': __( 'Colon', 'moon-seo' ),
	'.': __( 'Period', 'moon-seo' ),
	'•': __( 'Bullet', 'moon-seo' ),
	'*': __( 'Asterisk', 'moon-seo' ),
	'~': __( 'Tilde', 'moon-seo' ),
	'«': __( 'Left angle quotes', 'moon-seo' ),
	'»': __( 'Right angle quotes', 'moon-seo' ),
	'/': __( 'Slash', 'moon-seo' ),
	'\\': __( 'Backslash', 'moon-seo' ),
	'>': __( 'Greater than', 'moon-seo' ),
	'<': __( 'Less than', 'moon-seo' ),
};

// "labelledBy" is the id of a label element rendered by the caller.
export default function TitleSeparatorPicker( {
	value,
	onChange,
	labelledBy,
} ) {
	return (
		<div role="group" aria-labelledby={ labelledBy }>
			{ SEPARATORS.map( ( separator ) => (
				<Button
					key={ separator }
					variant={ value === separator ? 'primary' : 'secondary' }
					onClick={ () => onChange( separator ) }
					aria-pressed={ value === separator }
					aria-label={ SEPARATOR_LABELS[ separator ] }
				>
					{ separator }
				</Button>
			) ) }
		</div>
	);
}
