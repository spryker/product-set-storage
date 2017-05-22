<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Api;

use Spryker\Zed\Kernel\AbstractBundleConfig;

class ApiConfig extends AbstractBundleConfig
{

    const ROUTE_PREFIX_API_REST = '/api/rest/';

    const FORMAT_TYPE = 'json';

    const ACTION_CREATE = 'add';
    const ACTION_READ = 'get';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'remove';
    const ACTION_INDEX = 'find';

    const HTTP_CODE_SUCCESS = 200;
    const HTTP_CODE_CREATED = 201;
    const HTTP_CODE_NO_CONTENT = 204;
    const HTTP_CODE_PARTIAL_CONTENT = 206;
    const HTTP_CODE_NOT_FOUND = 404;
    const HTTP_CODE_VALIDATION_ERRORS = 422;
    const HTTP_CODE_INTERNAL_ERROR = 500;

    /**
     * @return int
     */
    public function getLimitPerPage()
    {
        return 20;
    }

    /**
     * @return int
     */
    public function getMaxLimitPerPage()
    {
        return 100;
    }

    /**
     * This returns the base URI to the API
     *
     * Modify if you want to include host and schema/protocol.
     *
     * @return int
     */
    public function getBaseUri()
    {
        return static::ROUTE_PREFIX_API_REST;
    }

}