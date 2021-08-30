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
use Google\Cloud\Compute\V1\Operation\Status;
use Google\Protobuf\Internal\Message;
use UnexpectedValueException;
use LogicException;

/**
 * Adapter for using Compute Operations
 *
 * @internal
 */
class AdditionalArgsClientWrapper implements ClientWrapperInterface
{
    private $operationsClient;

    /**
     * OperationResponse constructor.
     *
     * @param mixed $operationsClient
     */
    public function __construct($operationsClient)
    {
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
            : $lastProtoResponse->getStatus() === Status::DONE;
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
    public function getOperation($name, array $additionalArgs)
    {
        $args = array_merge([$name], $additionalArgs);
        return call_user_func_array([$this->operationsClient, 'get'], $args);
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
    public function cancelOperation($name, array $additionalArgs)
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
    public function deleteOperation($name, array $additionalArgs)
    {
        $args = array_merge([$name], $additionalArgs);
        return call_user_func_array([$this->operationsClient, 'delete'], $args);
    }
}
