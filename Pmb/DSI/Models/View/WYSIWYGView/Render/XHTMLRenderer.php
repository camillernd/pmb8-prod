<?php
// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: XHTMLRenderer.php,v 1.4.2.6 2024/11/18 13:59:31 jparis Exp $

namespace Pmb\DSI\Models\View\WYSIWYGView\Render;

use Pmb\Common\Helper\Helper;

class XHTMLRenderer extends HTML5Renderer
{
    public const CONTAINER_ELEMENT_TEMPLATE = '';

    protected function renderBlockElement($currentElement)
    {
        // D�terminer la structure du tableau en fonction de flexDirection
        $isColumn = $currentElement->style->flexDirection == "column";

        // On calcule la largeur de chaque bloc en fonction du nombre de blocs
        $defaultWidth = !$isColumn && !empty($currentElement->blocks) ? round(100 / count($currentElement->blocks)) . "%" : "100%";

        $elements = "";
        foreach ($currentElement->blocks as $block) {
            // D�terminer la largeur du bloc
            $width = ($block->widthEnabled ?? false) ? $block->style->width : $defaultWidth;
        
            // Appliquer un style personnalis� si le bloc est une image
            $customStyle = ($block->type === static::IMAGE_TYPE) ? "style='display:table; height:100%;'" : "";
        
            $elements .= $isColumn
                ? <<<HTML
                <tr>
                    <td width="{$width}" {$customStyle}>
                        {$this->render($block)}
                    </td>
                </tr>
                HTML
                : <<<HTML
                <td width="{$width}" {$customStyle}>
                    {$this->render($block)}
                </td>
                HTML;
        }

        $content = $isColumn
            ? <<<HTML
            <table width="100%" height="100%" cellpadding="0" cellspacing="0">
                {$elements}
            </table>
            HTML
            : <<<HTML
            <table width="100%" height="100%" cellpadding="0" cellspacing="0">
                <tr>{$elements}</tr>
            </table>
            HTML;

        // On retire les padding pour les placer sur le td pour la compatibilit� avec les anciens client mail Outlook
        $filteredStyle = $this->removePaddingProperties($currentElement->style);

        // Si il y'a un centrage auto, on ajoute l'attribut align pour centrer le contenu dans les anciens client mail Outlook
        $attributes = "";
        if (isset($filteredStyle->margin) && $filteredStyle->margin == "auto") {
            $attributes = "align='center'";
        }

        $tableStyle = $this->getStyleString($filteredStyle);

        // On r�cup�re les padding pour les mettre sur le td pour les ancien client mail Outlook
        $extractedStyle = $this->extractPaddingProperties($currentElement->style);
        $tdStyle = $this->getStyleString($extractedStyle);

        $attributes .= $this->convertCssToHtmlAttributes($tableStyle);

        return <<<HTML
        <table width="100%" height="100%" cellpadding="0" cellspacing="0" style="{$tableStyle}" {$attributes}>
            <tr>
                <td style="{$tdStyle}">
                    {$content}
                </td>
            </tr>
        </table>
        HTML;
    }

    protected function renderVideoElement($currentElement)
    {
        return "<!-- videos not supported -->";
    }

    protected function renderImageElement($currentElement)
    {
        //Ajout pour g�rer l'alignement vertical des images en HTML3
        // $currentElement->style->block->display = "table-cell";
        // $currentElement->style->block->verticalAlign = "middle";

        $style = $this->getMultimediaStyleString($currentElement->style->image, $currentElement->keepRatio);

        // On converti le style en attribut HTML pour les anciens clients mail
        $attributes = $this->convertCssToHtmlAttributes($style);

        if (!empty($currentElement->redirect)) {
            $content = <<<HTML
            <a href="{$currentElement->redirect}">
                <img alt="{$currentElement->alt}" style="{$style}" src="{$currentElement->content}" {$attributes}/>
            </a>
            HTML;
        } else {
            $content = <<<HTML
            <img alt="{$currentElement->alt}" style="{$style}" src="{$currentElement->content}" {$attributes}/>
            HTML;
        }

        $tableStyle = $this->getStyleString($currentElement->style->block);

        return <<<HTML
        <table width="100%" height="100%" cellpadding="0" cellspacing="0" style="{$tableStyle}">
            <tr>
                <td>{$content}</td>
            </tr>
        </table>
        HTML;
    }

    protected function renderRichTextElement($currentElement)
    {
        // R�cup�re le contenu HTML de l'�l�ment
        $content = $currentElement->content;
    
        // Applique margin: 0; � toutes les balises <p> sans perturber les autres styles
        $content = preg_replace_callback(
            '/<p([^>]*)>/i',
            function ($matches) {
                // R�cup�re le contenu des attributs de la balise <p>
                $attributes = $matches[1];
    
                // Si "style=" existe dans l'attribut, on l'ajuste
                if (strpos($attributes, 'style=') !== false) {
                    // On extrait le contenu du style existant
                    preg_match('/style="([^"]*)"/', $attributes, $styleMatches);
                    if (isset($styleMatches[1])) {
                        $style = $styleMatches[1];
                        // Ajoute margin: 0 seulement si ce n'est pas d�j� pr�sent
                        if (strpos($style, 'margin:') === false) {
                            $style .= ' margin: 0;';
                        }
                        // Remplace le style existant par le nouveau style
                        $attributes = str_replace($styleMatches[0], 'style="' . $style . '"', $attributes);
                    }
                } else {
                    // Si pas de style, on ajoute margin: 0
                    $attributes .= ' style="margin: 0;"';
                }
    
                // Retourne la balise <p> avec le nouvel attribut
                return '<p' . $attributes . '>';
            },
            $content
        );
    
        // V�rifie si le contenu contient "text-align: center"
        if (strpos($content, 'text-align: center') !== false) {
            // Encapsule le contenu dans une table avec un <td> centr�
            $content = <<<HTML
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center">{$content}</td>
                </tr>
            </table>
            HTML;
        }
    
        return $content;
    }

    protected function getStyleString($style): string
    {
        if (!is_object($style)) {
            return "";
        }

        if (isset($style->block)) {
            $style = $style->block;
        }

        $style = get_object_vars($style);
        $style = $this->convertToXHTML($style);

        array_walk($style, function (&$value, $attribute) {
            if($value == "") {
                return;
            }

            $value = "{$attribute}:{$value}";
        });

        return implode(';', $style);
    }

    protected function convertToXHTML($style)
    {
        $convertedStyle = array();
        foreach ($style as $attribute => $value) {
            $attribute = Helper::camelize_to_kebab($attribute);

            switch ($attribute) {
                case 'display':
                    if ($value === 'flex') {
                        $convertedStyle['display'] = 'table';
                    } else {
                        $convertedStyle['display'] = $value;
                    }
                    break;

                case 'flex':
                    if (!isset($style['width']) || $style['width'] == "") {
                        $convertedStyle['width'] = '100%';
                    }

                    $convertedStyle['height'] = '100%';
                    break;

                case 'flex-grow':
                    if (!isset($style['max-width']) || $style['max-width'] == "") {
                        $convertedStyle['min-width'] = '100%';
                    }

                    $convertedStyle['min-height'] = '100%';
                    break;

                case 'flex-direction':
                    // not compatible Xhtml
                    break;

                case 'justify-content':
                    switch ($value) {
                        default:
                        case 'start':
                            $convertedStyle['text-align'] = 'left';
                            break;
                        case 'center':
                            $convertedStyle['text-align'] = 'center';
                            break;
                        case 'end':
                            $convertedStyle['text-align'] = 'right';
                            break;
                    }
                    break;

                case 'align-items':
                    switch ($value) {
                        default:
                        case 'start':
                            $convertedStyle['vertical-align'] = 'top';
                            break;
                        case 'center':
                            $convertedStyle['vertical-align'] = 'middle';
                            break;
                        case 'end':
                            $convertedStyle['vertical-align'] = 'bottom';
                            break;
                    }
                    break;

                case 'border-radius':
                    $convertedStyle['border-radius'] = $value;

                    // Certaine border-radius ne passe pas si il n'y a pas de overflow
                    $convertedStyle['overflow'] = 'hidden';
                    break;

                default:
                    $convertedStyle[$attribute] = $value;
                    break;
            }
        }
        return $convertedStyle;
    }

    protected function removePaddingProperties($style): object
    {
        // Cr�er une copie de l'objet $style pour ne pas modifier l'original
        $cleanedStyle = clone ($style);

        // Propri�t�s CSS de padding � supprimer
        $paddingPropertiesToRemove = [
            'padding',
            'padding-left',
            'padding-right',
            'padding-top',
            'padding-bottom',
        ];

        // Supprimer les propri�t�s de padding de l'objet $cleanedStyle
        foreach ($paddingPropertiesToRemove as $property) {
            if (isset($cleanedStyle->$property)) {
                unset($cleanedStyle->$property);
            }
        }

        return $cleanedStyle;
    }

    protected function extractPaddingProperties($style): object
    {
        // Liste des propri�t�s CSS li�es au padding
        $paddingProperties = [
            'padding',
            'padding-left',
            'padding-right',
            'padding-top',
            'padding-bottom'
        ];

        // Filtrer les propri�t�s de padding pr�sentes dans $style
        $filteredPaddingStyles = array_filter(
            (array) $style,
            function ($value, $property) use ($paddingProperties) {
                return in_array($property, $paddingProperties);
            },
            ARRAY_FILTER_USE_BOTH
        );

        // Retourner les styles de padding sous forme d'objet
        return (object) $filteredPaddingStyles;
    }


    protected function convertCssToHtmlAttributes($css)
    {
        // Dictionnaire de mappage pour les styles CSS � leurs attributs HTML correspondants
        $cssToAttributes = [
            'background-color' => 'bgcolor',
            'color' => 'color',
            'text-align' => 'align',
            'vertical-align' => 'valign',
            'width' => 'width',
            'height' => 'height'
        ];

        // Extrait les propri�t�s CSS et leurs valeurs
        $attributes = "";
        $cssRules = explode(';', $css);

        foreach ($cssRules as $rule) {
            $rule = trim($rule);
            if (empty($rule))
                continue;

            list($property, $value) = explode(':', $rule);
            $property = trim($property);
            $value = trim($value);

            if($value == "") {
                continue;
            }

            // V�rifie la pr�sence de !important et le retire pour ajouter un attribut HTML sans !
            $isImportant = strpos($value, '!important') !== false;
            if ($isImportant) {
                $value = str_replace('!important', '', $value);
            }

            // Gestion sp�cifique pour width et height : suppression de l'unit� "px" si n�cessaire
            if ($property === 'width' || $property === 'height') {
                // Utilisation de str_replace pour retirer "px"
                $value = str_replace('px', '', $value);
            }

            // V�rifie si la propri�t� CSS peut �tre mapp�e � un attribut HTML
            if (isset($cssToAttributes[$property])) {

                // Ajoute l'attribut avec sa valeur
                $attributes .= ' ' . $cssToAttributes[$property] . '="' . htmlspecialchars($value, ENT_QUOTES) . ($isImportant ? ' !important' : '') . '"';
            }
        }

        return $attributes;
    }
}
