<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Adapters\Sleekdb\Statements;

use Quantum\Model\DbModel;
use RuntimeException;

/**
 * Trait RelatedCriteria
 * @package Quantum\Database
 */
trait RelatedCriteria
{
    abstract protected function buildJoinPath(string $currentPath, string $table): string;

    /**
     * Splits collected criteria into root-store and related-join scopes.
     * Related path criteria are mapped to join paths and excluded from root where.
     */
    protected function prepareCriteriaScopes(): void
    {
        $this->rootCriterias = $this->criterias;
        $this->relatedCriteriasByPath = [];
        $this->requiredRelatedPaths = [];

        if ($this->joins === [] || $this->criterias === []) {
            $this->criteriaPrepared = true;
            return;
        }

        $joinPaths = $this->collectJoinPaths();

        if ($joinPaths === []) {
            $this->criteriaPrepared = true;
            return;
        }

        $rootCriterias = [];
        $foundRelated = false;
        $hasOr = false;

        foreach ($this->criterias as $criteria) {
            if (is_string($criteria)) {
                $hasOr = $hasOr || strtoupper($criteria) === 'OR';
                $rootCriterias[] = $criteria;
                continue;
            }

            if (!is_array($criteria) || !isset($criteria[0]) || !is_string($criteria[0])) {
                $rootCriterias[] = $criteria;
                continue;
            }

            $column = $criteria[0];
            $relatedMatch = $this->matchRelatedPath($column, $joinPaths);

            if ($relatedMatch === null) {
                $rootCriterias[] = $criteria;
                continue;
            }

            $foundRelated = true;
            $path = $relatedMatch['path'];
            $localColumn = $relatedMatch['column'];

            $this->relatedCriteriasByPath[$path][] = [$localColumn, $criteria[1], $criteria[2] ?? null];
            $this->requiredRelatedPaths[] = $path;
        }

        if ($foundRelated && $hasOr) {
            throw new RuntimeException(
                'SleekDB related-model criterias do not support OR combinations with root/related scopes yet.'
            );
        }

        $this->requiredRelatedPaths = array_values(array_unique($this->requiredRelatedPaths));
        $this->rootCriterias = $rootCriterias;
        $this->criteriaPrepared = true;
    }

    /**
     * Builds all accessible join paths (including nested ones) from current join chain.
     * @return array<int, string>
     */
    protected function collectJoinPaths(): array
    {
        $paths = [];
        $this->collectJoinPathsRecursive($this->joins, 0, '', $paths);
        usort($paths, static fn (string $a, string $b): int => substr_count($b, '.') <=> substr_count($a, '.'));
        return array_values(array_unique($paths));
    }

    /**
     * Recursively resolves join paths while respecting switch mode for same-level joins.
     * @param array<int, array<string, mixed>> $joins
     * @param array<int, string> $paths
     */
    protected function collectJoinPathsRecursive(array $joins, int $level, string $currentPath, array &$paths): void
    {
        if (!isset($joins[$level])) {
            return;
        }

        $nextItem = $joins[$level];
        $model = unserialize($nextItem['model']);

        if (!$model instanceof DbModel) {
            return;
        }

        $joinPath = $this->buildJoinPath($currentPath, $model->table);
        $paths[] = $joinPath;

        $switch = (bool) ($nextItem['switch'] ?? true);

        if ($switch) {
            $this->collectJoinPathsRecursive($joins, $level + 1, $joinPath, $paths);
            return;
        }

        $this->collectJoinPathsRecursive($joins, $level + 1, $currentPath, $paths);
    }

    /**
     * Matches a dotted criteria column to the deepest known join path.
     * @param array<int, string> $joinPaths
     * @return array{path:string,column:string}|null
     */
    protected function matchRelatedPath(string $column, array $joinPaths): ?array
    {
        $parts = explode('.', $column);

        if (count($parts) <= 1) {
            return null;
        }

        if ($parts[0] === $this->table) {
            return null;
        }

        foreach ($joinPaths as $path) {
            $pathParts = explode('.', $path);

            if (count($parts) <= count($pathParts)) {
                continue;
            }

            if (array_slice($parts, 0, count($pathParts)) !== $pathParts) {
                continue;
            }

            $localColumn = implode('.', array_slice($parts, count($pathParts)));

            return [
                'path' => $path,
                'column' => $localColumn,
            ];
        }

        return null;
    }

    /**
     * Removes parent rows that do not contain data on required related paths.
     * @param array<int, array<string, mixed>> $results
     * @return array<int, array<string, mixed>>
     */
    public function applyRelatedCriteriaPostFilter(array $results): array
    {
        if ($this->requiredRelatedPaths === []) {
            return $results;
        }

        $results = array_values(array_filter($results, function (array $row): bool {
            foreach ($this->requiredRelatedPaths as $path) {
                if (!$this->pathHasData($row, explode('.', $path))) {
                    return false;
                }
            }

            return true;
        }));

        if ($this->autoSelectedRelatedRoots !== []) {
            $results = $this->removeAutoSelectedRelatedRoots($results);
        }

        return $results;
    }

    /**
     * Checks whether a nested relation path exists and contains at least one result.
     * @param array<string, mixed> $row
     * @param array<int, string> $segments
     */
    protected function pathHasData(array $row, array $segments): bool
    {
        $nodes = [$row];

        foreach ($segments as $segment) {
            $nextNodes = [];

            foreach ($nodes as $node) {
                if (!array_key_exists($segment, $node)) {
                    continue;
                }

                $value = $node[$segment];

                if (!is_array($value) || $value === []) {
                    continue;
                }

                if ($this->isList($value)) {
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $nextNodes[] = $item;
                        }
                    }
                    continue;
                }

                $nextNodes[] = $value;
            }

            if ($nextNodes === []) {
                return false;
            }

            $nodes = $nextNodes;
        }

        return true;
    }

    /**
     * Determines whether the given array has sequential integer keys from zero.
     * @param array<mixed> $value
     */
    protected function isList(array $value): bool
    {
        $index = 0;

        foreach ($value as $key => $_) {
            if ($key !== $index) {
                return false;
            }
            $index++;
        }

        return true;
    }

    /**
     * Adds related roots required for post-filtering to query selection only.
     * @return array<string, mixed>
     */
    protected function buildSelectForQuery(): array
    {
        $selected = $this->selected;
        $this->autoSelectedRelatedRoots = [];

        if ($this->requiredRelatedPaths === []) {
            return $selected;
        }

        foreach ($this->requiredRelatedPaths as $path) {
            $relatedRoot = explode('.', $path)[0];

            if ($this->selectReferencesRoot($selected, $relatedRoot)) {
                continue;
            }

            $selected[] = $relatedRoot;
            $this->autoSelectedRelatedRoots[] = $relatedRoot;
        }

        return $selected;
    }

    /**
     * Checks whether current selection already references the given relation root.
     * @param array<string, mixed> $selected
     */
    protected function selectReferencesRoot(array $selected, string $root): bool
    {
        foreach ($selected as $column) {
            if (!is_string($column)) {
                continue;
            }

            if ($column === $root || str_starts_with($column, $root . '.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Removes relation roots auto-selected only for post-filter evaluation.
     * @param array<int, array<string, mixed>> $results
     * @return array<int, array<string, mixed>>
     */
    protected function removeAutoSelectedRelatedRoots(array $results): array
    {
        foreach ($results as &$row) {
            foreach ($this->autoSelectedRelatedRoots as $root) {
                unset($row[$root]);
            }
        }
        unset($row);

        return $results;
    }
}
