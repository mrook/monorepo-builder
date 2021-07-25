<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace MonorepoBuilder20210725\Symfony\Component\Console\Helper;

use MonorepoBuilder20210725\Symfony\Component\Console\Exception\InvalidArgumentException;
use MonorepoBuilder20210725\Symfony\Component\Console\Exception\RuntimeException;
use MonorepoBuilder20210725\Symfony\Component\Console\Formatter\OutputFormatter;
use MonorepoBuilder20210725\Symfony\Component\Console\Formatter\WrappableOutputFormatterInterface;
use MonorepoBuilder20210725\Symfony\Component\Console\Output\ConsoleSectionOutput;
use MonorepoBuilder20210725\Symfony\Component\Console\Output\OutputInterface;
/**
 * Provides helpers to display a table.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Саша Стаменковић <umpirsky@gmail.com>
 * @author Abdellatif Ait boudad <a.aitboudad@gmail.com>
 * @author Max Grigorian <maxakawizard@gmail.com>
 * @author Dany Maillard <danymaillard93b@gmail.com>
 */
class Table
{
    private const SEPARATOR_TOP = 0;
    private const SEPARATOR_TOP_BOTTOM = 1;
    private const SEPARATOR_MID = 2;
    private const SEPARATOR_BOTTOM = 3;
    private const BORDER_OUTSIDE = 0;
    private const BORDER_INSIDE = 1;
    private $headerTitle;
    private $footerTitle;
    /**
     * Table headers.
     */
    private $headers = [];
    /**
     * Table rows.
     */
    private $rows = [];
    private $horizontal = \false;
    /**
     * Column widths cache.
     */
    private $effectiveColumnWidths = [];
    /**
     * Number of columns cache.
     *
     * @var int
     */
    private $numberOfColumns;
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var TableStyle
     */
    private $style;
    /**
     * @var array
     */
    private $columnStyles = [];
    /**
     * User set column widths.
     *
     * @var array
     */
    private $columnWidths = [];
    private $columnMaxWidths = [];
    private static $styles;
    private $rendered = \false;
    public function __construct(\MonorepoBuilder20210725\Symfony\Component\Console\Output\OutputInterface $output)
    {
        $this->output = $output;
        if (!self::$styles) {
            self::$styles = self::initStyles();
        }
        $this->setStyle('default');
    }
    /**
     * Sets a style definition.
     * @param string $name
     * @param \Symfony\Component\Console\Helper\TableStyle $style
     */
    public static function setStyleDefinition($name, $style)
    {
        if (!self::$styles) {
            self::$styles = self::initStyles();
        }
        self::$styles[$name] = $style;
    }
    /**
     * Gets a style definition by name.
     *
     * @return TableStyle
     * @param string $name
     */
    public static function getStyleDefinition($name)
    {
        if (!self::$styles) {
            self::$styles = self::initStyles();
        }
        if (isset(self::$styles[$name])) {
            return self::$styles[$name];
        }
        throw new \MonorepoBuilder20210725\Symfony\Component\Console\Exception\InvalidArgumentException(\sprintf('Style "%s" is not defined.', $name));
    }
    /**
     * Sets table style.
     *
     * @param TableStyle|string $name The style name or a TableStyle instance
     *
     * @return $this
     */
    public function setStyle($name)
    {
        $this->style = $this->resolveStyle($name);
        return $this;
    }
    /**
     * Gets the current table style.
     *
     * @return TableStyle
     */
    public function getStyle()
    {
        return $this->style;
    }
    /**
     * Sets table column style.
     *
     * @param TableStyle|string $name The style name or a TableStyle instance
     *
     * @return $this
     * @param int $columnIndex
     */
    public function setColumnStyle($columnIndex, $name)
    {
        $this->columnStyles[$columnIndex] = $this->resolveStyle($name);
        return $this;
    }
    /**
     * Gets the current style for a column.
     *
     * If style was not set, it returns the global table style.
     *
     * @return TableStyle
     * @param int $columnIndex
     */
    public function getColumnStyle($columnIndex)
    {
        return $this->columnStyles[$columnIndex] ?? $this->getStyle();
    }
    /**
     * Sets the minimum width of a column.
     *
     * @return $this
     * @param int $columnIndex
     * @param int $width
     */
    public function setColumnWidth($columnIndex, $width)
    {
        $this->columnWidths[$columnIndex] = $width;
        return $this;
    }
    /**
     * Sets the minimum width of all columns.
     *
     * @return $this
     * @param mixed[] $widths
     */
    public function setColumnWidths($widths)
    {
        $this->columnWidths = [];
        foreach ($widths as $index => $width) {
            $this->setColumnWidth($index, $width);
        }
        return $this;
    }
    /**
     * Sets the maximum width of a column.
     *
     * Any cell within this column which contents exceeds the specified width will be wrapped into multiple lines, while
     * formatted strings are preserved.
     *
     * @return $this
     * @param int $columnIndex
     * @param int $width
     */
    public function setColumnMaxWidth($columnIndex, $width)
    {
        if (!$this->output->getFormatter() instanceof \MonorepoBuilder20210725\Symfony\Component\Console\Formatter\WrappableOutputFormatterInterface) {
            throw new \LogicException(\sprintf('Setting a maximum column width is only supported when using a "%s" formatter, got "%s".', \MonorepoBuilder20210725\Symfony\Component\Console\Formatter\WrappableOutputFormatterInterface::class, \get_debug_type($this->output->getFormatter())));
        }
        $this->columnMaxWidths[$columnIndex] = $width;
        return $this;
    }
    /**
     * @param mixed[] $headers
     */
    public function setHeaders($headers)
    {
        $headers = \array_values($headers);
        if (!empty($headers) && !\is_array($headers[0])) {
            $headers = [$headers];
        }
        $this->headers = $headers;
        return $this;
    }
    /**
     * @param mixed[] $rows
     */
    public function setRows($rows)
    {
        $this->rows = [];
        return $this->addRows($rows);
    }
    /**
     * @param mixed[] $rows
     */
    public function addRows($rows)
    {
        foreach ($rows as $row) {
            $this->addRow($row);
        }
        return $this;
    }
    public function addRow($row)
    {
        if ($row instanceof \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableSeparator) {
            $this->rows[] = $row;
            return $this;
        }
        if (!\is_array($row)) {
            throw new \MonorepoBuilder20210725\Symfony\Component\Console\Exception\InvalidArgumentException('A row must be an array or a TableSeparator instance.');
        }
        $this->rows[] = \array_values($row);
        return $this;
    }
    /**
     * Adds a row to the table, and re-renders the table.
     * @return $this
     */
    public function appendRow($row)
    {
        if (!$this->output instanceof \MonorepoBuilder20210725\Symfony\Component\Console\Output\ConsoleSectionOutput) {
            throw new \MonorepoBuilder20210725\Symfony\Component\Console\Exception\RuntimeException(\sprintf('Output should be an instance of "%s" when calling "%s".', \MonorepoBuilder20210725\Symfony\Component\Console\Output\ConsoleSectionOutput::class, __METHOD__));
        }
        if ($this->rendered) {
            $this->output->clear($this->calculateRowCount());
        }
        $this->addRow($row);
        $this->render();
        return $this;
    }
    /**
     * @param mixed[] $row
     */
    public function setRow($column, $row)
    {
        $this->rows[$column] = $row;
        return $this;
    }
    /**
     * @return $this
     * @param string|null $title
     */
    public function setHeaderTitle($title)
    {
        $this->headerTitle = $title;
        return $this;
    }
    /**
     * @return $this
     * @param string|null $title
     */
    public function setFooterTitle($title)
    {
        $this->footerTitle = $title;
        return $this;
    }
    /**
     * @return $this
     * @param bool $horizontal
     */
    public function setHorizontal($horizontal = \true)
    {
        $this->horizontal = $horizontal;
        return $this;
    }
    /**
     * Renders table to output.
     *
     * Example:
     *
     *     +---------------+-----------------------+------------------+
     *     | ISBN          | Title                 | Author           |
     *     +---------------+-----------------------+------------------+
     *     | 99921-58-10-7 | Divine Comedy         | Dante Alighieri  |
     *     | 9971-5-0210-0 | A Tale of Two Cities  | Charles Dickens  |
     *     | 960-425-059-0 | The Lord of the Rings | J. R. R. Tolkien |
     *     +---------------+-----------------------+------------------+
     */
    public function render()
    {
        $divider = new \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableSeparator();
        if ($this->horizontal) {
            $rows = [];
            foreach ($this->headers[0] ?? [] as $i => $header) {
                $rows[$i] = [$header];
                foreach ($this->rows as $row) {
                    if ($row instanceof \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableSeparator) {
                        continue;
                    }
                    if (isset($row[$i])) {
                        $rows[$i][] = $row[$i];
                    } elseif ($rows[$i][0] instanceof \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableCell && $rows[$i][0]->getColspan() >= 2) {
                        // Noop, there is a "title"
                    } else {
                        $rows[$i][] = null;
                    }
                }
            }
        } else {
            $rows = \array_merge($this->headers, [$divider], $this->rows);
        }
        $this->calculateNumberOfColumns($rows);
        $rows = $this->buildTableRows($rows);
        $this->calculateColumnsWidth($rows);
        $isHeader = !$this->horizontal;
        $isFirstRow = $this->horizontal;
        $hasTitle = (bool) $this->headerTitle;
        foreach ($rows as $row) {
            if ($divider === $row) {
                $isHeader = \false;
                $isFirstRow = \true;
                continue;
            }
            if ($row instanceof \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableSeparator) {
                $this->renderRowSeparator();
                continue;
            }
            if (!$row) {
                continue;
            }
            if ($isHeader || $isFirstRow) {
                $this->renderRowSeparator($isHeader ? self::SEPARATOR_TOP : self::SEPARATOR_TOP_BOTTOM, $hasTitle ? $this->headerTitle : null, $hasTitle ? $this->style->getHeaderTitleFormat() : null);
                $isFirstRow = \false;
                $hasTitle = \false;
            }
            if ($this->horizontal) {
                $this->renderRow($row, $this->style->getCellRowFormat(), $this->style->getCellHeaderFormat());
            } else {
                $this->renderRow($row, $isHeader ? $this->style->getCellHeaderFormat() : $this->style->getCellRowFormat());
            }
        }
        $this->renderRowSeparator(self::SEPARATOR_BOTTOM, $this->footerTitle, $this->style->getFooterTitleFormat());
        $this->cleanup();
        $this->rendered = \true;
    }
    /**
     * Renders horizontal header separator.
     *
     * Example:
     *
     *     +-----+-----------+-------+
     */
    private function renderRowSeparator(int $type = self::SEPARATOR_MID, string $title = null, string $titleFormat = null)
    {
        if (0 === ($count = $this->numberOfColumns)) {
            return;
        }
        $borders = $this->style->getBorderChars();
        if (!$borders[0] && !$borders[2] && !$this->style->getCrossingChar()) {
            return;
        }
        $crossings = $this->style->getCrossingChars();
        if (self::SEPARATOR_MID === $type) {
            [$horizontal, $leftChar, $midChar, $rightChar] = [$borders[2], $crossings[8], $crossings[0], $crossings[4]];
        } elseif (self::SEPARATOR_TOP === $type) {
            [$horizontal, $leftChar, $midChar, $rightChar] = [$borders[0], $crossings[1], $crossings[2], $crossings[3]];
        } elseif (self::SEPARATOR_TOP_BOTTOM === $type) {
            [$horizontal, $leftChar, $midChar, $rightChar] = [$borders[0], $crossings[9], $crossings[10], $crossings[11]];
        } else {
            [$horizontal, $leftChar, $midChar, $rightChar] = [$borders[0], $crossings[7], $crossings[6], $crossings[5]];
        }
        $markup = $leftChar;
        for ($column = 0; $column < $count; ++$column) {
            $markup .= \str_repeat($horizontal, $this->effectiveColumnWidths[$column]);
            $markup .= $column === $count - 1 ? $rightChar : $midChar;
        }
        if (null !== $title) {
            $titleLength = \MonorepoBuilder20210725\Symfony\Component\Console\Helper\Helper::width(\MonorepoBuilder20210725\Symfony\Component\Console\Helper\Helper::removeDecoration($formatter = $this->output->getFormatter(), $formattedTitle = \sprintf($titleFormat, $title)));
            $markupLength = \MonorepoBuilder20210725\Symfony\Component\Console\Helper\Helper::width($markup);
            if ($titleLength > ($limit = $markupLength - 4)) {
                $titleLength = $limit;
                $formatLength = \MonorepoBuilder20210725\Symfony\Component\Console\Helper\Helper::width(\MonorepoBuilder20210725\Symfony\Component\Console\Helper\Helper::removeDecoration($formatter, \sprintf($titleFormat, '')));
                $formattedTitle = \sprintf($titleFormat, \MonorepoBuilder20210725\Symfony\Component\Console\Helper\Helper::substr($title, 0, $limit - $formatLength - 3) . '...');
            }
            $titleStart = \intdiv($markupLength - $titleLength, 2);
            if (\false === \mb_detect_encoding($markup, null, \true)) {
                $markup = \substr_replace($markup, $formattedTitle, $titleStart, $titleLength);
            } else {
                $markup = \mb_substr($markup, 0, $titleStart) . $formattedTitle . \mb_substr($markup, $titleStart + $titleLength);
            }
        }
        $this->output->writeln(\sprintf($this->style->getBorderFormat(), $markup));
    }
    /**
     * Renders vertical column separator.
     */
    private function renderColumnSeparator(int $type = self::BORDER_OUTSIDE) : string
    {
        $borders = $this->style->getBorderChars();
        return \sprintf($this->style->getBorderFormat(), self::BORDER_OUTSIDE === $type ? $borders[1] : $borders[3]);
    }
    /**
     * Renders table row.
     *
     * Example:
     *
     *     | 9971-5-0210-0 | A Tale of Two Cities  | Charles Dickens  |
     */
    private function renderRow(array $row, string $cellFormat, string $firstCellFormat = null)
    {
        $rowContent = $this->renderColumnSeparator(self::BORDER_OUTSIDE);
        $columns = $this->getRowColumns($row);
        $last = \count($columns) - 1;
        foreach ($columns as $i => $column) {
            if ($firstCellFormat && 0 === $i) {
                $rowContent .= $this->renderCell($row, $column, $firstCellFormat);
            } else {
                $rowContent .= $this->renderCell($row, $column, $cellFormat);
            }
            $rowContent .= $this->renderColumnSeparator($last === $i ? self::BORDER_OUTSIDE : self::BORDER_INSIDE);
        }
        $this->output->writeln($rowContent);
    }
    /**
     * Renders table cell with padding.
     */
    private function renderCell(array $row, int $column, string $cellFormat) : string
    {
        $cell = $row[$column] ?? '';
        $width = $this->effectiveColumnWidths[$column];
        if ($cell instanceof \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableCell && $cell->getColspan() > 1) {
            // add the width of the following columns(numbers of colspan).
            foreach (\range($column + 1, $column + $cell->getColspan() - 1) as $nextColumn) {
                $width += $this->getColumnSeparatorWidth() + $this->effectiveColumnWidths[$nextColumn];
            }
        }
        // str_pad won't work properly with multi-byte strings, we need to fix the padding
        if (\false !== ($encoding = \mb_detect_encoding($cell, null, \true))) {
            $width += \strlen($cell) - \mb_strwidth($cell, $encoding);
        }
        $style = $this->getColumnStyle($column);
        if ($cell instanceof \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableSeparator) {
            return \sprintf($style->getBorderFormat(), \str_repeat($style->getBorderChars()[2], $width));
        }
        $width += \MonorepoBuilder20210725\Symfony\Component\Console\Helper\Helper::length($cell) - \MonorepoBuilder20210725\Symfony\Component\Console\Helper\Helper::length(\MonorepoBuilder20210725\Symfony\Component\Console\Helper\Helper::removeDecoration($this->output->getFormatter(), $cell));
        $content = \sprintf($style->getCellRowContentFormat(), $cell);
        $padType = $style->getPadType();
        if ($cell instanceof \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableCell && $cell->getStyle() instanceof \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableCellStyle) {
            $isNotStyledByTag = !\preg_match('/^<(\\w+|(\\w+=[\\w,]+;?)*)>.+<\\/(\\w+|(\\w+=\\w+;?)*)?>$/', $cell);
            if ($isNotStyledByTag) {
                $cellFormat = $cell->getStyle()->getCellFormat();
                if (!\is_string($cellFormat)) {
                    $tag = \http_build_query($cell->getStyle()->getTagOptions(), '', ';');
                    $cellFormat = '<' . $tag . '>%s</>';
                }
                if (\strstr($content, '</>')) {
                    $content = \str_replace('</>', '', $content);
                    $width -= 3;
                }
                if (\strstr($content, '<fg=default;bg=default>')) {
                    $content = \str_replace('<fg=default;bg=default>', '', $content);
                    $width -= \strlen('<fg=default;bg=default>');
                }
            }
            $padType = $cell->getStyle()->getPadByAlign();
        }
        return \sprintf($cellFormat, \str_pad($content, $width, $style->getPaddingChar(), $padType));
    }
    /**
     * Calculate number of columns for this table.
     */
    private function calculateNumberOfColumns(array $rows)
    {
        $columns = [0];
        foreach ($rows as $row) {
            if ($row instanceof \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableSeparator) {
                continue;
            }
            $columns[] = $this->getNumberOfColumns($row);
        }
        $this->numberOfColumns = \max($columns);
    }
    private function buildTableRows(array $rows) : \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableRows
    {
        /** @var WrappableOutputFormatterInterface $formatter */
        $formatter = $this->output->getFormatter();
        $unmergedRows = [];
        for ($rowKey = 0; $rowKey < \count($rows); ++$rowKey) {
            $rows = $this->fillNextRows($rows, $rowKey);
            // Remove any new line breaks and replace it with a new line
            foreach ($rows[$rowKey] as $column => $cell) {
                $colspan = $cell instanceof \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableCell ? $cell->getColspan() : 1;
                if (isset($this->columnMaxWidths[$column]) && \MonorepoBuilder20210725\Symfony\Component\Console\Helper\Helper::width(\MonorepoBuilder20210725\Symfony\Component\Console\Helper\Helper::removeDecoration($formatter, $cell)) > $this->columnMaxWidths[$column]) {
                    $cell = $formatter->formatAndWrap($cell, $this->columnMaxWidths[$column] * $colspan);
                }
                if (!\strstr($cell ?? '', "\n")) {
                    continue;
                }
                $escaped = \implode("\n", \array_map([\MonorepoBuilder20210725\Symfony\Component\Console\Formatter\OutputFormatter::class, 'escapeTrailingBackslash'], \explode("\n", $cell)));
                $cell = $cell instanceof \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableCell ? new \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableCell($escaped, ['colspan' => $cell->getColspan()]) : $escaped;
                $lines = \explode("\n", \str_replace("\n", "<fg=default;bg=default>\n</>", $cell));
                foreach ($lines as $lineKey => $line) {
                    if ($colspan > 1) {
                        $line = new \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableCell($line, ['colspan' => $colspan]);
                    }
                    if (0 === $lineKey) {
                        $rows[$rowKey][$column] = $line;
                    } else {
                        if (!\array_key_exists($rowKey, $unmergedRows) || !\array_key_exists($lineKey, $unmergedRows[$rowKey])) {
                            $unmergedRows[$rowKey][$lineKey] = $this->copyRow($rows, $rowKey);
                        }
                        $unmergedRows[$rowKey][$lineKey][$column] = $line;
                    }
                }
            }
        }
        return new \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableRows(function () use($rows, $unmergedRows) : \Traversable {
            foreach ($rows as $rowKey => $row) {
                (yield $row instanceof \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableSeparator ? $row : $this->fillCells($row));
                if (isset($unmergedRows[$rowKey])) {
                    foreach ($unmergedRows[$rowKey] as $row) {
                        (yield $row instanceof \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableSeparator ? $row : $this->fillCells($row));
                    }
                }
            }
        });
    }
    private function calculateRowCount() : int
    {
        $numberOfRows = \count(\iterator_to_array($this->buildTableRows(\array_merge($this->headers, [new \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableSeparator()], $this->rows))));
        if ($this->headers) {
            ++$numberOfRows;
            // Add row for header separator
        }
        if (\count($this->rows) > 0) {
            ++$numberOfRows;
            // Add row for footer separator
        }
        return $numberOfRows;
    }
    /**
     * fill rows that contains rowspan > 1.
     *
     * @throws InvalidArgumentException
     */
    private function fillNextRows(array $rows, int $line) : array
    {
        $unmergedRows = [];
        foreach ($rows[$line] as $column => $cell) {
            if (null !== $cell && !$cell instanceof \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableCell && !\is_scalar($cell) && !(\is_object($cell) && \method_exists($cell, '__toString'))) {
                throw new \MonorepoBuilder20210725\Symfony\Component\Console\Exception\InvalidArgumentException(\sprintf('A cell must be a TableCell, a scalar or an object implementing "__toString()", "%s" given.', \get_debug_type($cell)));
            }
            if ($cell instanceof \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableCell && $cell->getRowspan() > 1) {
                $nbLines = $cell->getRowspan() - 1;
                $lines = [$cell];
                if (\strstr($cell, "\n")) {
                    $lines = \explode("\n", \str_replace("\n", "<fg=default;bg=default>\n</>", $cell));
                    $nbLines = \count($lines) > $nbLines ? \substr_count($cell, "\n") : $nbLines;
                    $rows[$line][$column] = new \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableCell($lines[0], ['colspan' => $cell->getColspan(), 'style' => $cell->getStyle()]);
                    unset($lines[0]);
                }
                // create a two dimensional array (rowspan x colspan)
                $unmergedRows = \array_replace_recursive(\array_fill($line + 1, $nbLines, []), $unmergedRows);
                foreach ($unmergedRows as $unmergedRowKey => $unmergedRow) {
                    $value = $lines[$unmergedRowKey - $line] ?? '';
                    $unmergedRows[$unmergedRowKey][$column] = new \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableCell($value, ['colspan' => $cell->getColspan(), 'style' => $cell->getStyle()]);
                    if ($nbLines === $unmergedRowKey - $line) {
                        break;
                    }
                }
            }
        }
        foreach ($unmergedRows as $unmergedRowKey => $unmergedRow) {
            // we need to know if $unmergedRow will be merged or inserted into $rows
            if (isset($rows[$unmergedRowKey]) && \is_array($rows[$unmergedRowKey]) && $this->getNumberOfColumns($rows[$unmergedRowKey]) + $this->getNumberOfColumns($unmergedRows[$unmergedRowKey]) <= $this->numberOfColumns) {
                foreach ($unmergedRow as $cellKey => $cell) {
                    // insert cell into row at cellKey position
                    \array_splice($rows[$unmergedRowKey], $cellKey, 0, [$cell]);
                }
            } else {
                $row = $this->copyRow($rows, $unmergedRowKey - 1);
                foreach ($unmergedRow as $column => $cell) {
                    if (!empty($cell)) {
                        $row[$column] = $unmergedRow[$column];
                    }
                }
                \array_splice($rows, $unmergedRowKey, 0, [$row]);
            }
        }
        return $rows;
    }
    /**
     * fill cells for a row that contains colspan > 1.
     */
    private function fillCells(iterable $row)
    {
        $newRow = [];
        foreach ($row as $column => $cell) {
            $newRow[] = $cell;
            if ($cell instanceof \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableCell && $cell->getColspan() > 1) {
                foreach (\range($column + 1, $column + $cell->getColspan() - 1) as $position) {
                    // insert empty value at column position
                    $newRow[] = '';
                }
            }
        }
        return $newRow ?: $row;
    }
    private function copyRow(array $rows, int $line) : array
    {
        $row = $rows[$line];
        foreach ($row as $cellKey => $cellValue) {
            $row[$cellKey] = '';
            if ($cellValue instanceof \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableCell) {
                $row[$cellKey] = new \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableCell('', ['colspan' => $cellValue->getColspan()]);
            }
        }
        return $row;
    }
    /**
     * Gets number of columns by row.
     */
    private function getNumberOfColumns(array $row) : int
    {
        $columns = \count($row);
        foreach ($row as $column) {
            $columns += $column instanceof \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableCell ? $column->getColspan() - 1 : 0;
        }
        return $columns;
    }
    /**
     * Gets list of columns for the given row.
     */
    private function getRowColumns(array $row) : array
    {
        $columns = \range(0, $this->numberOfColumns - 1);
        foreach ($row as $cellKey => $cell) {
            if ($cell instanceof \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableCell && $cell->getColspan() > 1) {
                // exclude grouped columns.
                $columns = \array_diff($columns, \range($cellKey + 1, $cellKey + $cell->getColspan() - 1));
            }
        }
        return $columns;
    }
    /**
     * Calculates columns widths.
     */
    private function calculateColumnsWidth(iterable $rows)
    {
        for ($column = 0; $column < $this->numberOfColumns; ++$column) {
            $lengths = [];
            foreach ($rows as $row) {
                if ($row instanceof \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableSeparator) {
                    continue;
                }
                foreach ($row as $i => $cell) {
                    if ($cell instanceof \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableCell) {
                        $textContent = \MonorepoBuilder20210725\Symfony\Component\Console\Helper\Helper::removeDecoration($this->output->getFormatter(), $cell);
                        $textLength = \MonorepoBuilder20210725\Symfony\Component\Console\Helper\Helper::width($textContent);
                        if ($textLength > 0) {
                            $contentColumns = \str_split($textContent, \ceil($textLength / $cell->getColspan()));
                            foreach ($contentColumns as $position => $content) {
                                $row[$i + $position] = $content;
                            }
                        }
                    }
                }
                $lengths[] = $this->getCellWidth($row, $column);
            }
            $this->effectiveColumnWidths[$column] = \max($lengths) + \MonorepoBuilder20210725\Symfony\Component\Console\Helper\Helper::width($this->style->getCellRowContentFormat()) - 2;
        }
    }
    private function getColumnSeparatorWidth() : int
    {
        return \MonorepoBuilder20210725\Symfony\Component\Console\Helper\Helper::width(\sprintf($this->style->getBorderFormat(), $this->style->getBorderChars()[3]));
    }
    private function getCellWidth(array $row, int $column) : int
    {
        $cellWidth = 0;
        if (isset($row[$column])) {
            $cell = $row[$column];
            $cellWidth = \MonorepoBuilder20210725\Symfony\Component\Console\Helper\Helper::width(\MonorepoBuilder20210725\Symfony\Component\Console\Helper\Helper::removeDecoration($this->output->getFormatter(), $cell));
        }
        $columnWidth = $this->columnWidths[$column] ?? 0;
        $cellWidth = \max($cellWidth, $columnWidth);
        return isset($this->columnMaxWidths[$column]) ? \min($this->columnMaxWidths[$column], $cellWidth) : $cellWidth;
    }
    /**
     * Called after rendering to cleanup cache data.
     */
    private function cleanup()
    {
        $this->effectiveColumnWidths = [];
        $this->numberOfColumns = null;
    }
    private static function initStyles() : array
    {
        $borderless = new \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableStyle();
        $borderless->setHorizontalBorderChars('=')->setVerticalBorderChars(' ')->setDefaultCrossingChar(' ');
        $compact = new \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableStyle();
        $compact->setHorizontalBorderChars('')->setVerticalBorderChars(' ')->setDefaultCrossingChar('')->setCellRowContentFormat('%s');
        $styleGuide = new \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableStyle();
        $styleGuide->setHorizontalBorderChars('-')->setVerticalBorderChars(' ')->setDefaultCrossingChar(' ')->setCellHeaderFormat('%s');
        $box = (new \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableStyle())->setHorizontalBorderChars('─')->setVerticalBorderChars('│')->setCrossingChars('┼', '┌', '┬', '┐', '┤', '┘', '┴', '└', '├');
        $boxDouble = (new \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableStyle())->setHorizontalBorderChars('═', '─')->setVerticalBorderChars('║', '│')->setCrossingChars('┼', '╔', '╤', '╗', '╢', '╝', '╧', '╚', '╟', '╠', '╪', '╣');
        return ['default' => new \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableStyle(), 'borderless' => $borderless, 'compact' => $compact, 'symfony-style-guide' => $styleGuide, 'box' => $box, 'box-double' => $boxDouble];
    }
    private function resolveStyle($name) : \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableStyle
    {
        if ($name instanceof \MonorepoBuilder20210725\Symfony\Component\Console\Helper\TableStyle) {
            return $name;
        }
        if (isset(self::$styles[$name])) {
            return self::$styles[$name];
        }
        throw new \MonorepoBuilder20210725\Symfony\Component\Console\Exception\InvalidArgumentException(\sprintf('Style "%s" is not defined.', $name));
    }
}
