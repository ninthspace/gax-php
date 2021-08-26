<?php
/*
 * Copyright 2021 Google LLC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *
 *     * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above
 * copyright notice, this list of conditions and the following disclaimer
 * in the documentation and/or other materials provided with the
 * distribution.
 *     * Neither the name of Google Inc. nor the names of its
 * contributors may be used to endorse or promote products derived from
 * this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

namespace Google\ApiCore\LongRunning\ClientWrapper;

use Google\ApiCore\ApiException;
use Google\Cloud\Compute\V1\GlobalOperationsClient;
use Google\Cloud\Compute\V1\GlobalOrganizationOperationsGapicClient;
use Google\Cloud\Compute\V1\RegionOperationsClient;
use Google\Cloud\Compute\V1\ZoneOperationsClient;
use Google\Cloud\Compute\V1\Operation\Status;
use Google\Protobuf\Internal\Message;
use UnexpectedValueException;
use LogicException;

/**
 * Adapter for using Compute Operations
 *
 * @internal
 */
class Compute implements ClientWrapperInterface
{
    const TYPE_GLOBAL = GlobalOperationsClient::class;
    const TYPE_GLOBAL_ORGANIZATION = GlobalOrganizationOperationsClient::class;
    const TYPE_REGION = RegionOperationsClient::class;
    const TYPE_ZONE = ZoneOperationsClient::class;

    private $operationsClient;
    private $operationsClientType;

    private $zone;
    private $project;
    private $region;

    /**
     * OperationResponse constructor.
     *
     * @param mixed $operationsClient
     */
    public function __construct($operationsClient, array $options)
    {
        if ($operationsClient instanceof GlobalOperationsClient) {
            if (!isset($options['project'])) {
                throw new UnexpectedValueException(
                    'GlobalOperationsClient requires project option'
                );
            }
            $this->project = $options['project'];
            $this->operationsClientType = self::TYPE_GLOBAL;
        } elseif ($operationsClient instanceof GlobalOrganizationOperationsGapicClient) {
            $this->operationsClientType = self::TYPE_GLOBAL_ORGANIZATION;
        } elseif ($operationsClient instanceof RegionOperationsClient) {
            if (!isset($options['project']) || !isset($options['region'])) {
                throw new UnexpectedValueException(
                    'RegionOperationsClient requires project and region option'
                );
            }
            $this->project = $options['project'];
            $this->region = $options['region'];
            $this->operationsClientType = self::TYPE_REGION;
        } elseif ($operationsClient instanceof ZoneOperationsClient) {
            if (!isset($options['project']) || !isset($options['zone'])) {
                throw new UnexpectedValueException(
                    'ZoneOperationsClient requires project and zone option'
                );
            }
            $this->project = $options['project'];
            $this->zone = $options['zone'];
            $this->operationsClientType = self::TYPE_ZONE;
        } else {
            throw new UnexpectedValueException('Operation client not supported');
        }
        $this->operationsClient = $operationsClient;
    }

    /**
     * Check whether the operation has completed.
     *
     * @return bool
     */
    public function isDone($lastProtoResponse = null)
    {
        return (is_null($lastProtoResponse) || is_null($lastProtoResponse->getStatus()))
            ? false
            : $lastProtoResponse->geStatus() === Status::DONE;
    }

    /**
     * @return mixed The OperationsClient object used to make requests to the
     * operations API.
     */
    public function getOperationsClient()
    {
        return $this->operationsClient;
    }

    /**
     * Get the operation.
     *
     * @param string $name
     *
     * @return Message
     *
     * @throws ApiException If the API call fails.
     */
    public function getOperation($name)
    {
        switch ($this->operationsClientType) {
            case self::TYPE_GLOBAL:
                return $this->globalOperationsClient->get($name, $this->project);

            case self::TYPE_GLOBAL_ORGANIZATION:
                return $this->globalOrganizationOperationsClient->get($name);

            case self::TYPE_REGION:
                return $this->regionOperationsClient->get($name, $this->project, $this->region);

            case self::TYPE_ZONE:
                return $this->zoneOperationsClient->get($name, $this->project, $this->zone);
        }
        throw new LogicException('Invalid operations client');
    }

    /**
     * Starts asynchronous cancellation on a long-running operation. The server
     * makes a best effort to cancel the operation, but success is not
     * guaranteed. If the server doesn't support this method, it will throw an
     * ApiException with code \Google\Rpc\Code::UNIMPLEMENTED. Clients can continue
     * to use reload and pollUntilComplete methods to check whether the cancellation
     * succeeded or whether the operation completed despite cancellation.
     * On successful cancellation, the operation is not deleted; instead, it becomes
     * an operation with a getError() value with a \Google\Rpc\Status code of 1,
     * corresponding to \Google\Rpc\Code::CANCELLED.
     *
     * @param string $name
     *
     * @throws ApiException If the API call fails.
     */
    public function cancel($name)
    {
        throw new LogicException('cancelling operations is not supported by this API');
    }

    /**
     * Delete the long-running operation. This method indicates that the client is
     * no longer interested in the operation result. It does not cancel the operation.
     * If the server doesn't support this method, it will throw an ApiException with
     * code \Google\Rpc\Code::UNIMPLEMENTED.
     *
     * @param string $name
     *
     * @throws ApiException If the API call fails.
     */
    public function delete($name)
    {
        switch ($this->operationsClientType) {
            case self::TYPE_GLOBAL:
                return $this->globalOperationsClient->delete($name, $this->project);

            case self::TYPE_GLOBAL_ORGANIZATION:
                return $this->globalOrganizationOperationsClient->delete($name);

            case self::TYPE_REGION:
                return $this->regionOperationsClient->delete($name, $this->project, $this->region);

            case self::TYPE_ZONE:
                return $this->zoneOperationsClient->delete($name, $this->project, $this->zone);
        }
        throw new LogicException('Invalid operations client');
    }
}
