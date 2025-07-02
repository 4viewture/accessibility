<?php
namespace FourViewture\Accessibility\ViewHelpers\Math;


use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class SumViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('a', 'mixed', 'First number for calculation');
        $this->registerArgument('b', 'mixed', 'Optional: Second number or Iterator/Traversable/Array for calculation');
    }

    protected function render(): int
    {
        return (int)$this->arguments['a'] + (int)$this->arguments['b'];
    }
}
