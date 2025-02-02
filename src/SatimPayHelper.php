<?php

declare(strict_types=1);

namespace PiteurStudio;

use PiteurStudio\Exception\SatimMissingDataException;

trait SatimPayHelper
{
    /**
     * The response data from the register() method.
     *
     * @var array<string, mixed>|null
     */
    private ?array $registerOrderResponse = null;

    /**
     * The response data from the confirm() method.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $confirmOrderResponse = null;

    /**
     * The response data from the status() method.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $statusOrderResponse = null;

    /**
     * The response data from the refund() method.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $refundOrderResponse = null;

    /**
     * Retrieves the response data for the registered order.
     *
     * This method retrieves the response data associated with the registered order.
     * The response data is stored when the registerOrder() method is called.
     * If registerOrder() was not called before calling this method, a SatimMissingDataException is thrown.
     *
     * @return array<string, mixed> The response data associated with the registered order.
     *
     * @throws SatimMissingDataException If the register(), confirm(), refund(), or status() method  was not called first.
     */
    public function getResponse(): array
    {

        if ($this->context == 'register') {
            if (empty($this->registerOrderResponse)) {
                throw new SatimMissingDataException(
                    'No payment data found. Call register() first to register the order and obtain the response data.'
                );
            }

            return $this->registerOrderResponse;
        }

        if ($this->context == 'confirm') {
            if (empty($this->confirmOrderResponse)) {
                throw new SatimMissingDataException(
                    'No payment data found. Call confirm() first to confirm the order and obtain the response data.'
                );
            }

            return $this->confirmOrderResponse;
        }

        if ($this->context == 'refund') {
            if (empty($this->refundOrderResponse)) {
                throw new SatimMissingDataException(
                    'No payment data found. Call refund() first to refund the order and obtain the response data.'
                );
            }

            return $this->refundOrderResponse;
        }

        if ($this->context == 'status') {
            if (empty($this->statusOrderResponse)) {
                throw new SatimMissingDataException(
                    'No payment data found. Call status() first to get the order status and obtain the response data.'
                );
            }

            return $this->statusOrderResponse;
        }

        throw new SatimMissingDataException(
            'No response data found. Call one of the methods first to obtain the response data.'
        );
    }

    /**
     * Retrieves the order ID from the response data.
     *
     * This method extracts the order ID from the response data obtained from the Satim API.
     * It relies on the getResponse() method to retrieve the response data.
     *
     * @return string The order ID extracted from the response data.
     *
     * @throws SatimMissingDataException If the registerOrder() was not called first.
     */
    public function getOrderId(): string
    {
        // Retrieve the response data from the getResponse() method
        /** @var array<string, string> $responseData */
        $responseData = $this->getResponse();

        // Extract the order ID from the response data
        return $responseData['orderId'];
    }

    /**
     * Retrieve the IP address of the user from the response data.
     *
     * @return string|null The IP address of the user extracted from the response data.
     *
     * @throws SatimMissingDataException
     */
    public function getIpAddress(): ?string
    {
        // Retrieve the response data from the getResponse() method
        /** @var array<string, string> $responseData */
        $responseData = $this->getResponse();

        // Extract the order ID from the response data
        return $responseData['Ip'] ?? null;
    }

    /**
     * Retrieve the cardholder name from the response data.
     *
     * @return string|null The IP address of the user extracted from the response data.
     *
     * @throws SatimMissingDataException
     */
    public function getCardHolderName(): ?string
    {
        // Retrieve the response data from the getResponse() method
        /** @var array<string, string> $responseData */
        $responseData = $this->getResponse();

        // Extract the order ID from the response data
        return $responseData['cardholderName'] ?? null;
    }

    /**
     * Retrieve the card expiry date from the response data.
     *
     * @return string|null The card expiry date extracted from the response data.
     *
     * @throws SatimMissingDataException
     */
    public function getCardExpiry(): ?string
    {
        // Retrieve the response data from the getResponse() method
        /** @var array<string, string> $responseData */
        $responseData = $this->getResponse();

        // Extract the order ID from the response data
        return $responseData['expiration'] ?? null;
    }

    /**
     * Retrieve the card PAN from the response data.
     *
     * @return string|null The card PAN extracted from the response data.
     *
     * @throws SatimMissingDataException
     */
    public function getCardPan(): ?string
    {
        // Retrieve the response data from the getResponse() method
        /** @var array<string, string> $responseData */
        $responseData = $this->getResponse();

        // Extract the order ID from the response data
        return $responseData['Pan'] ?? null;
    }

    /**
     * Retrieve the approval code from the response data.
     *
     * @return string|null The approval code extracted from the response data.
     *
     * @throws SatimMissingDataException
     */
    public function getApprovalCode(): ?string
    {
        // Retrieve the response data from the getResponse() method
        /** @var array<string, string> $responseData */
        $responseData = $this->getResponse();

        // Extract the order ID from the response data
        return $responseData['approvalCode'] ?? null;
    }

    /**
     * Redirects the user to the URL specified in the response data.
     *
     * This method sends an HTTP header to perform the redirection and terminates the script execution.
     * This method should be used after the registerOrder() method has been called to redirect the user to the
     * payment form.
     *
     * @throws SatimMissingDataException If the registerOrder() was not called first.
     */
    // ( This method is not testable as it terminates the script execution )
    // @codeCoverageIgnoreStart
    public function redirect(): void
    {
        // Retrieve the URL from the getResponse() method
        $url = $this->getUrl();

        // Set the HTTP header for redirection
        header('Location: '.$url);

        // Terminate the script execution to prevent any further code execution
        exit;
    }

    // @codeCoverageIgnoreEnd

    /**
     * Retrieves the URL of the payment form from the response data.
     *
     * @return string The URL of the payment form associated with the response data.
     *
     * @throws SatimMissingDataException If the registerOrder() was not called first.
     */
    public function getUrl(): string
    {
        // Retrieve the response data from the getResponse() method
        /** @var array<string, string> $responseData */
        $responseData = $this->getResponse();

        if (! isset($responseData['formUrl'])) {
            throw new SatimMissingDataException(
                'No payment form URL found. Call register() first to register the order and obtain the response data.'
            );
        }

        // Extract the URL from the response data
        return $responseData['formUrl'];
    }
}
