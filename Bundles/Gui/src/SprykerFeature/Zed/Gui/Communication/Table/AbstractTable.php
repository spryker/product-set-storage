<?php

/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace SprykerFeature\Zed\Gui\Communication\Table;

use Generated\Zed\Ide\AutoCompletion;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Map\TableMap;
use SprykerEngine\Zed\Kernel\Locator;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractTable
{

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var AutoCompletion
     */
    private $locator;

    /**
     * @var array
     */
    private $data;

    /**
     * @var TableConfiguration
     */
    private $config;

    /**
     * @var int
     */
    private $total;

    /**
     * @var int
     */
    private $filtered = 0;

    /**
     * @var int
     */
    protected $defaultLimit = 10;

    /**
     * @var string
     */
    protected $defaultUrl = 'table';

    /**
     * @var string
     */
    protected $tableClass;

    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @var string
     */
    protected $tableIdentifier;

    /**
     * @return $this
     */
    private function init()
    {
        if (!$this->initialized) {
            $this->initialized = true;
            $this->locator = Locator::getInstance();
            $this->request = $this->locator->application()
                ->pluginPimple()
                ->getApplication()['request']
            ;
            $config = $this->newTableConfiguration();
            $config->setPageLength($this->getLimit());
            $config = $this->configure($config);
            $this->setConfiguration($config);
        }

        return $this;
    }

    /**
     * @todo find a better solution (remove it)
     *
     * @param string $name
     *
     * @return string
     * @deprecated this method should not be needed.
     */
    public function buildAlias($name)
    {
        return str_replace(
            ['.', '(', ')'],
            '',
            $name
        );
    }

    /**
     * @return TableConfiguration
     */
    protected function newTableConfiguration()
    {
        return new TableConfiguration();
    }

    /**
     * @param TableConfiguration $config
     *
     * @return mixed
     */
    abstract protected function configure(TableConfiguration $config);

    /**
     * @param TableConfiguration $config
     */
    public function setConfiguration(TableConfiguration $config)
    {
        $this->config = $config;
    }

    /**
     * @param TableConfiguration $config
     *
     * @return mixed
     */
    abstract protected function prepareData(TableConfiguration $config);

    /**
     * @param array $data
     */
    public function loadData(array $data)
    {
        $tableData = [];

        $headers = $this->config->getHeader();
        $isArray = is_array($headers);
        foreach ($data as $row) {
            if ($isArray) {
                $row = array_intersect_key($row, $headers);

                $row = $this->reOrderByHeaders($headers, $row);
            }

            $tableData[] = array_values($row);
        }

        $this->setData($tableData);
    }

    /**
     * @param array $headers
     * @param array $row
     *
     * @return array
     */
    protected function reOrderByHeaders(array $headers, array $row)
    {
        $result = [];

        foreach ($headers as $key => $value) {
            $result[$key] = $row[$key];
        }

        return $result;
    }

    /**
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return TableConfiguration
     */
    public function getConfiguration()
    {
        return $this->config;
    }

    /**
     * @return string
     */
    public function getTableIdentifier()
    {
        if (null === $this->tableIdentifier) {
            $this->generateTableIdentifier();
        }

        return $this->tableIdentifier;
    }

    /**
     * @param string $prefix
     *
     * @return $this
     */
    protected function generateTableIdentifier($prefix = 'table-')
    {
        $this->tableIdentifier = uniqid($prefix);

        return $this;
    }

    /**
     * @param null $tableIdentifier
     */
    public function setTableIdentifier($tableIdentifier)
    {
        $this->tableIdentifier = $tableIdentifier;
    }

    /**
     * @return \Twig_Environment
     * @throws \LogicException
     */
    private function getTwig()
    {
        /** @var \Twig_Environment $twig */
        $twig = $this->locator->application()
            ->pluginPimple()
            ->getApplication()['twig']
        ;

        if ($twig === null) {
            throw new \LogicException('Twig environment not set up.');
        }

        /** @var \Twig_Loader_Chain $loaderChain */
        $loaderChain = $twig->getLoader();
        $loaderChain->addLoader(new \Twig_Loader_Filesystem(__DIR__ . '/../../Presentation/Table/'));

        return $twig;
    }

    /**
     * @return mixed
     */
    public function getOffset()
    {
        return $this->request->query->get('start', 0);
    }

    /**
     * @return mixed
     */
    public function getOrders()
    {
        return $this->request->query->get('order', [
            [
                'column' => 0,
                'dir' => 'asc',
            ],
        ]);
    }

    /**
     * @return mixed
     */
    public function getSearchTerm()
    {
        return $this->request->query->get('search', null);
    }

    /**
     * @return mixed
     */
    public function getLimit()
    {
        return $this->request->query->get('length', $this->defaultLimit);
    }

    /**
     * @return string
     */
    public function render()
    {
        $this->init();

        $twigVars = [
            'config' => $this->prepareConfig(),
        ];

        return $this->getTwig()
            ->render('index.twig', $twigVars)
        ;
    }

    /**
     * @return array
     */
    public function prepareConfig()
    {
        if ($this->getConfiguration() instanceof TableConfiguration) {
            $configArray = [
                'tableId' => $this->getTableIdentifier(),
                'class' => $this->tableClass,
                'header' => $this->config->getHeader(),
                'searchable' => $this->config->getSearchable(),
                'sortable' => $this->config->getSortable(),
                'pageLength' => $this->config->getPageLength(),
                'url' => (true === is_null($this->config->getUrl())) ? $this->defaultUrl : $this->config->getUrl(),
            ];
        } else {
            $configArray = [
                'tableId' => $this->getTableIdentifier(),
                'class' => $this->tableClass,
                'url' => $this->defaultUrl,
                'header' => [],
            ];
        }

        return $configArray;
    }

    /**
     * @todo to be rafactored, does to many things and is hard to understand
     *
     * @param ModelCriteria $query
     * @param TableConfiguration $config
     *
     * @return array
     */
    protected function runQuery(ModelCriteria $query, TableConfiguration $config)
    {
        $limit = $config->getPageLength();
        $offset = $this->getOffset();
        $order = $this->getOrders();
        $columns = array_keys($config->getHeader());
        $orderColumn = $columns[$order[0]['column']];
        $this->total = $query->count();
        $query->orderBy($orderColumn, $order[0]['dir']);
        $searchTerm = $this->getSearchTerm();

        if (mb_strlen($searchTerm['value']) > 0) {
            $isFirst = true;
            $query->setIdentifierQuoting(true);

            foreach ($config->getSearchable() as $value) {
                if (!$isFirst) {
                    $query->_or();
                } else {
                    $isFirst = false;
                }

                $query->where(sprintf("LOWER(%s) LIKE '%s'", $value, '%' . mb_strtolower($searchTerm['value']) . '%'));
            }

            $this->filtered = $query->count();
        } else {
            $this->filtered = $this->total;
        }

        $query->offset($offset)
            ->limit($limit)
        ;
        $data = $query->find();

        return $data->toArray(null, false, TableMap::TYPE_COLNAME);
    }

    /**
     * @return array
     */
    public function fetchData()
    {
        $this->init();

        $data = $this->prepareData($this->config);
        $this->loadData($data);
        $wrapperArray = [
            'draw' => $this->request->query->get('draw', 1),
            'recordsTotal' => $this->total,
            'recordsFiltered' => $this->filtered,
            'data' => $this->data,
        ];

        return $wrapperArray;
    }

    /**
     * Drop table name from key
     *
     * @param string $key
     *
     * @return string
     */
    public function cutTablePrefix($key)
    {
        $position = mb_strpos($key, '.');

        return (false !== $position) ? mb_substr($key, $position + 1) : $key;
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public function camelize($str)
    {
        return str_replace(' ', '', ucwords(mb_strtolower(str_replace('_', ' ', $str))));
    }
}