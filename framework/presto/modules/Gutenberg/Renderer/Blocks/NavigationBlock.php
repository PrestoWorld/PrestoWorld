<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Navigation Block rendering core/navigation
 */
class NavigationBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $inner = $this->renderInner($context);
        
        $containerClasses = array_merge(['wp-block-navigation__container'], $this->classes);
        $containerClassAttr = ' class="' . implode(' ', array_unique($containerClasses)) . '"';
        
        $wrappedInner = "<ul{$containerClassAttr}>{$inner}</ul>";
        
        if (empty($inner)) {
            $wrappedInner = "<ul{$containerClassAttr}><li class=\"wp-block-navigation-item\"><a class=\"wp-block-navigation-item__content\" href=\"/\"><span class=\"wp-block-navigation-item__label\">Home</span></a></li></ul>";
        }

        $classAttr = !empty($this->classes) ? ' class="' . implode(' ', $this->classes) . '"' : '';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';

        // Interactivity API attributes
        $interactivityAttrs = ' data-wp-interactive="core/navigation" data-wp-context=\'{"overlayOpenedBy":{"click":false,"hover":false,"focus":false},"type":"overlay","roleAttribute":"","ariaLabel":"Menu"}\'';
        
        $responsiveButton = '<button aria-haspopup="dialog" aria-label="Open menu" class="wp-block-navigation__responsive-container-open" data-wp-on--click="actions.openMenuOnClick" data-wp-on--keydown="actions.handleMenuKeydown"><svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 7.5h16v1.5H4z"></path><path d="M4 15h16v1.5H4z"></path></svg></button>';
        
        $modal = '<div class="wp-block-navigation__responsive-container" id="modal-1" data-wp-class--is-menu-open="state.isMenuOpen" data-wp-watch="callbacks.initMenu" data-wp-on--keydown="actions.handleMenuKeydown" data-wp-on--focusout="actions.handleMenuFocusout" tabindex="-1">';
        $modal .= '<div class="wp-block-navigation__responsive-close" tabindex="-1"><div class="wp-block-navigation__responsive-dialog" data-wp-bind--aria-modal="state.ariaModal" data-wp-bind--aria-label="state.ariaLabel" data-wp-bind--role="state.roleAttribute">';
        $modal .= '<button aria-label="Close menu" class="wp-block-navigation__responsive-container-close" data-wp-on--click="actions.closeMenuOnClick"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="m13.06 12 6.47-6.47-1.06-1.06L12 10.94 5.53 4.47 4.47 5.53 10.94 12l-6.47 6.47 1.06 1.06L12 13.06l6.47 6.47 1.06-1.06L13.06 12Z"></path></svg></button>';
        $modal .= '<div class="wp-block-navigation__responsive-container-content" data-wp-watch="callbacks.focusFirstElement" id="modal-1-content">' . $wrappedInner . '</div>';
        $modal .= '</div></div></div>';

        return "<nav{$classAttr}{$styleAttr}{$interactivityAttrs}>{$responsiveButton}{$modal}</nav>";
    }
}
