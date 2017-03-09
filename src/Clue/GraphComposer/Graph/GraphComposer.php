<?php

namespace Clue\GraphComposer\Graph;

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Attribute\AttributeAware;
use Fhaculty\Graph\Attribute\AttributeBagNamespaced;
use Fhaculty\Graph\Vertex;
use Graphp\GraphViz\GraphViz;

class GraphComposer
{
    const DEFAULT_COLOR = '#1A2833';

    private $layoutVertex = array(
        'fillcolor' => '#eeeeee',
        'style' => 'filled, rounded',
        'shape' => 'box',
        'fontcolor' => '#314B5F',
    );

    private $layoutVertexRoot = array(
        'style' => 'filled, rounded, bold',
    );

    private $layoutEdge = array(
        'fontcolor' => '#767676',
        'fontsize' => 10,
        'color' => '#1A2833',
    );

    private $layoutEdgeDev = array(
        'style' => 'dashed',
    );

    private $dependencyGraph;

    private $filter;

    private $showVersion = true;

    private $usedPackages = [];

    /**
     * @var GraphViz
     */
    private $graphviz;

    /**
     * @param array         $dirs
     * @param GraphViz|null $graphviz
     */
    public function __construct($dirs, GraphViz $graphviz = null)
    {
        if ($graphviz === null) {
            $graphviz = new GraphViz();
            $graphviz->setFormat('svg');
        }
        $analyzer = new \JMS\Composer\DependencyAnalyzer();
        if (!is_array($dirs)) {
            $dirs = array($dirs);
        }
        if (!empty($dirs)) {
            foreach ($dirs as $dir) {
                $color = self::DEFAULT_COLOR;
                if (strpos($dir, ':')) {
                    list($dir, $color) = explode(':', $dir);
                }
                $this->dependencyGraph[] = [
                    'color' => $color,
                    'dependencies' => $analyzer->analyze($dir),
                ];
            }
        }
        $this->graphviz = $graphviz;
    }

    /**
     * @return \Fhaculty\Graph\Graph
     */
    public function createGraph()
    {
        $graph = new Graph();
        if (empty($this->dependencyGraph)) {
            return $graph;
        }

        foreach ($this->dependencyGraph as $dependencyGraph) {
            foreach ($dependencyGraph['dependencies']->getPackages() as $package) {
                // Apply filter if necessary to get a light graph
                $name = $package->getName();
                if (!$this->matchFilter($name)) {
                    continue;
                }

                // Create package graph block
                $start = $graph->createVertex($name, true);

                // Define dependency layout
                $layoutVertex = $this->layoutVertex;
                $label = $name;
                if ($this->showVersion && $package->getVersion() !== null) {
                    $label .= ': '.$package->getVersion();
                }
                $layoutVertex['label'] = $label;
                $layoutVertex['color'] = in_array($name, $this->usedPackages) ? self::DEFAULT_COLOR : $dependencyGraph['color'];
                $this->setLayout($start, $layoutVertex);

                // Save used package name
                $this->usedPackages[] = $name;

                foreach ($package->getOutEdges() as $requires) {
                    // Apply filter if necessary to get a light graph
                    $targetName = $requires->getDestPackage()->getName();
                    if (!$this->matchFilter($targetName)) {
                        continue;
                    }
                    // Create dependency graph block
                    $target = $graph->createVertex($targetName, true);

                    // Create edge
                    $edge = $this->getEdgeBetween($start, $target, $requires->getVersionConstraint(), $dependencyGraph['color']);

                    if ($requires->isDevDependency()) {
                        $this->setLayout($edge, $this->layoutEdgeDev);
                    }
                }
            }

            $root = $graph->getVertex($dependencyGraph['dependencies']->getRootPackage()->getName());
            $this->setLayout($root, $this->layoutVertexRoot);
        }

        return $graph;
    }

    /**
     * Create or get existing edge between to points.
     *
     * @param Vertex $start
     * @param Vertex $target
     * @param string $label
     * @param string $color
     *
     * @return \Fhaculty\Graph\Edge\Base|\Fhaculty\Graph\Edge\Directed
     */
    private function getEdgeBetween(Vertex $start, Vertex $target, $label = '', $color = self::DEFAULT_COLOR)
    {
        // Define edge layout and label
        $edgeLayout = $this->layoutEdge;
        if ($this->showVersion) {
            $edgeLayout['label'] = $label;
        }
        $edgeLayout['color'] = $color;

        if ($start->hasEdgeTo($target)) {
            $edge = $start->getEdgesTo($target)->getEdgeFirst();
            // If edge already exists, define default color
            $edgeLayout['color'] = self::DEFAULT_COLOR;
        } else {
            $edge = $start->createEdgeTo($target);
        }

        // Define edge layout
        $this->setLayout($edge, $edgeLayout);

        return $edge;
    }

    /**
     * Check if dependency name matches specified filter.
     *
     * @param $name
     *
     * @return bool
     */
    private function matchFilter($name)
    {
        if (empty($this->filter) || strpos($name, $this->filter) !== false) {
            return true;
        }

        return false;
    }

    private function setLayout(AttributeAware $entity, array $layout)
    {
        $bag = new AttributeBagNamespaced($entity->getAttributeBag(), 'graphviz.');
        $bag->setAttributes($layout);

        return $entity;
    }

    public function displayGraph()
    {
        $graph = $this->createGraph();

        $this->graphviz->display($graph);
    }

    public function getImagePath()
    {
        $graph = $this->createGraph();

        return $this->graphviz->createImageFile($graph);
    }

    public function setFormat($format)
    {
        $this->graphviz->setFormat($format);

        return $this;
    }

    /**
     * @return string
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @param string $filter
     *
     * @return $this
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * @return bool
     */
    public function isShowVersion()
    {
        return $this->showVersion;
    }

    /**
     * @param bool $showVersion
     *
     * @return $this
     */
    public function setShowVersion($showVersion)
    {
        $this->showVersion = $showVersion;

        return $this;
    }
}
