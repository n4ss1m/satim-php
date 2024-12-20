<?php

declare(strict_types=1);

namespace PiteurStudio;

use PiteurStudio\Client\HttpClientService;
use PiteurStudio\Exception\SatimInvalidDataException;
use PiteurStudio\Exception\SatimMissingDataException;
use PiteurStudio\Exception\SatimUnexpectedResponseException;

class Satim extends SatimConfig
{
    use SatimPayHelper;
    use SatimStatusChecker;

    protected HttpClientService $httpClientService;

    /**
     * Create a new Satim instance.
     *
     * This method will create a new Satim instance with the provided configuration data.
     * If the required data is missing, it will throw a SatimMissingDataException.
     *
     * @param array $data The configuration data for the Satim client. This should contain the following keys:
     *     - username: The username for the Satim API.
     *     - password: The password for the Satim API.
     *     - terminal_id: The terminal ID for the Satim API.
     * @param HttpClientService|null $httpClientService The HTTP client service to use for making requests to the Satim API. If not provided, a new instance will be created.
     *
     * @throws SatimMissingDataException Thrown if the required data is missing.
     */
    public function __construct(array $data, ?HttpClientService $httpClientService = null)
    {
        $this->httpClientService = $httpClientService ?? new HttpClientService($this->test_mode); // Automatically create HttpClientService if not provided
        $this->initFromArray($data);
    }

    /**
     * Validate payment data before making a request.
     *
     * This method checks if the required data is set and performs necessary actions.
     * If the required data is missing, it throws a SatimMissingDataException.
     *
     * @throws SatimMissingDataException Thrown if the required data is missing.
     */
    private function validateData(): void
    {
        // Check if the return URL is set, throw an exception if missing
        if (! $this->returnUrl) {
            throw new SatimMissingDataException('Return URL missing. Call returnUrl() to set it.');
        }

        // Check if the order number is set; if not, generate a random one
        if (! $this->orderNumber) {
            $this->orderNumber(mt_rand(1000000000, 9999999999));
        }

        // Check if the amount is set, throw an exception if missing
        if (! $this->amount) {
            throw new SatimMissingDataException('Amount missing. Call the amount() method to set it.');
        }

    }

    /**
     * Build the request data for payment registration.
     *
     * This method will create the data array to be sent to the Satim API
     * for payment registration.
     */
    private function buildData(): array
    {
        // Add force_terminal_id that will be sent in the request
        $additionalData = [
            'force_terminal_id' => $this->terminal_id,
        ];

        // If user-defined fields are set, add them to the additional data
        if ($this->userDefinedFields) {
            $additionalData = array_merge($additionalData, $this->userDefinedFields);
        }

        // Create the main request data array
        $data = [
            'userName' => $this->username,
            'password' => $this->password,
            'orderNumber' => $this->orderNumber,
            'amount' => $this->amount * 100, // convert to minor units
            'currency' => $this->currency,
            'returnUrl' => $this->returnUrl,
            'failUrl' => $this->failUrl ?? $this->returnUrl,
            'language' => $this->language,
            'jsonParams' => json_encode($additionalData),
        ];

        // If a description is set, add it to the request data
        if ($this->description) {
            $data['description'] = $this->description;
        }

        // If a session timeout is set, add it to the request data
        if ($this->sessionTimeoutSecs) {
            $data['sessionTimeoutSecs'] = $this->sessionTimeoutSecs;
        }

        // Return the request data
        return $data;
    }

    /**
     * Register a payment with Satim API.
     *
     * This method will register a payment on the Satim API and store the response data in the registerOrderResponse property.
     *
     * @throws SatimInvalidDataException Thrown if the request data is invalid.
     * @throws SatimMissingDataException Thrown if the required data is missing.
     * @throws SatimUnexpectedResponseException Thrown if the API response is unexpected.
     */
    public function registerOrder(): static
    {

        // Validate the data before sending the request
        $this->validateData();

        // Build the request data
        $data = $this->buildData();

        // Send the request and store the response
        $result = $this->httpClientService->handleApiRequest('/register.do', $data);

        // Check the response and throw an exception if the error code is not 0
        if ($result['errorCode'] !== '0') {

            $errorMessage = $result['errorMessage'] ?? 'Unknown error';

            throw new SatimUnexpectedResponseException('registerPayment Error {errorCode: '.$result['errorCode'].' , errorMessage: '.$errorMessage.'}');
        }

        // Store the response data
        $this->registerOrderResponse = $result;

        return $this;

    }

    /**
     * Confirm the payment with Satim API.
     *
     * This method sends a request to the Satim API to confirm the payment
     * using the given order ID. The response is stored in the confirmOrderResponse property.
     *
     * @param string $orderId The ID of the order to be confirmed.
     *
     * @return static The current instance for method chaining.
     *
     * @throws SatimUnexpectedResponseException Thrown if the API response is unexpected.
     */
    public function confirmOrder(string $orderId): static
    {
        // Prepare the data for the confirmation request
        $data = [
            'userName' => $this->username,
            'password' => $this->password,
            'orderId' => $orderId,
            'language' => $this->language,
        ];

        // Send the request and store the response
        $this->confirmOrderResponse = $this->httpClientService->handleApiRequest('/confirmOrder.do', $data);

        return $this;
    }

    /**
     * Retrieve the status of a payment from Satim API.
     *
     * This method sends a request to the Satim API to check the status of a payment
     * using the given order ID. The response is stored in the confirmPaymentData property.
     *
     * @param string $orderId The ID of the order for which the status is to be checked.
     *
     * @return static The current instance for method chaining.
     *
     * @throws SatimUnexpectedResponseException Thrown if the API response is unexpected.
     */
    public function checkOrderStatus(string $orderId): static
    {
        // Prepare the data for the status request
        $data = [
            'userName' => $this->username,
            'password' => $this->password,
            'orderId' => $orderId,
            'language' => $this->language,
        ];

        // Send request to Satim API and store the response
        $this->confirmOrderResponse = $this->httpClientService->handleApiRequest('/getOrderStatus.do', $data);

        return $this;
    }

    /**
     * Refund a payment with Satim API.
     *
     * This method sends a refund request for a specified order ID and amount.
     * The amount should be specified in the major currency unit and will be
     * converted to minor units in the request.
     *
     * @param string $orderId The ID of the order to be refunded.
     * @param int $amount The amount to refund in major currency units.
     *
     * @return array The response from the Satim API.
     *
     * @throws SatimUnexpectedResponseException Thrown if the API response is unexpected.
     */
    public function refundPayment(string $orderId, int $amount): array
    {
        // Prepare the data for the refund request
        $data = [
            'userName' => $this->username,
            'password' => $this->password,
            'orderId' => $orderId,
            'amount' => $amount * 100,
            'language' => $this->language,
        ];

        return $this->httpClientService->handleApiRequest('/refund.do', $data);
    }
}
