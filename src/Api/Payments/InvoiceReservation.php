<?php
/**
 * Copyright (c) 2016 Martin Aarhof
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Altapay\Api\Payments;

use Altapay\AbstractApi;
use Altapay\Exceptions;
use Altapay\Exceptions\ResponseHeaderException;
use Altapay\Exceptions\ResponseMessageException;
use Altapay\Response\InvoiceReservationResponse;
use Altapay\Serializer\ResponseSerializer;
use Altapay\Traits;
use Altapay\Types;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\ClientException as GuzzleHttpClientException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvoiceReservation extends AbstractApi
{
    use Traits\TerminalTrait;
    use Traits\ShopOrderIdTrait;
    use Traits\AmountTrait;
    use Traits\CurrencyTrait;
    use Traits\TransactionInfoTrait;
    use Traits\CustomerInfoTrait;
    use Traits\OrderlinesTrait;

    /**
     * The type of payment.
     *
     * @param string $type
     *
     * @return void
     */
    public function setType($type)
    {
        $this->unresolvedOptions['type'] = $type;
    }

    /**
     * For Arvato germany an account number and bank code (BLZ) can be passed in, to pay via a secure elv bank transfer.
     *
     * @param string $accountnumber
     *
     * @return void
     */
    public function setAccountNumber($accountnumber)
    {
        $this->unresolvedOptions['accountNumber'] = $accountnumber;
    }

    /**
     * The source of the payment
     *
     * @param string $paymentsource
     *
     * @return void
     */
    public function setPaymentSource($paymentsource)
    {
        $this->unresolvedOptions['payment_source'] = $paymentsource;
    }

    /**
     * For Arvato germany an account number and bank code (BLZ) can be passed in, to pay via a secure elv bank transfer
     *
     * @param string $bankcode
     *
     * @return void
     */
    public function setBankCode($bankcode)
    {
        $this->unresolvedOptions['bankCode'] = $bankcode;
    }

    /**
     * If you wish to decide pr. Payment wich fraud detection service to use
     *
     * @param string $fraudservice
     *
     * @return void
     */
    public function setFraudService($fraudservice)
    {
        $this->unresolvedOptions['fraud_service'] = $fraudservice;
    }

    /**
     * Configure options
     *
     * @param OptionsResolver $resolver
     *
     * @return void
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'terminal', 'shop_orderid', 'amount',
            'currency', 'type', 'payment_source',
        ]);

        $resolver->setAllowedValues('type', Types\PaymentTypes::getAllowed());
        $resolver->setDefault('type', 'payment');
        $resolver->setAllowedValues('payment_source', Types\PaymentSources::getAllowed());
        $resolver->setDefault('payment_source', 'eCommerce');

        $resolver->setDefined([
            'accountNumber', 'bankCode', 'fraud_service', 'customer_info', 'orderLines',
            'transaction_info'
        ]);
        $resolver->setAllowedTypes('accountNumber', 'string');
        $resolver->setAllowedTypes('bankCode', 'string');
        $resolver->setAllowedValues('fraud_service', Types\FraudServices::getAllowed());
    }

    /**
     * Handle response
     *
     * @param Request $request
     * @param ResponseInterface $response
     *
     * @return InvoiceReservationResponse
     * @throws \Exception
     */
    protected function handleResponse(Request $request, ResponseInterface $response)
    {
        $body = (string) $response->getBody();
        $xml = new \SimpleXMLElement($body);

        return ResponseSerializer::serialize(InvoiceReservationResponse::class, $xml->Body, $xml->Header);
    }

    /**
     * @return array<string, string>
     */
    protected function getBasicHeaders()
    {
        $headers = parent::getBasicHeaders();
        if (mb_strtolower($this->getHttpMethod()) === 'post') {
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        return $headers;
    }

    /**
     * Url to api call
     *
     * @param array<string, mixed> $options Resolved options
     *
     * @return string
     */
    protected function getUrl(array $options)
    {
        $url = 'createInvoiceReservation';
        if (mb_strtolower($this->getHttpMethod()) === 'get') {
            $query = $this->buildUrl($options);
            $url   = sprintf('%s/?%s', $url, $query);
        }

        return $url;
    }

    /**
     * @return string
     */
    protected function getHttpMethod()
    {
        return 'POST';
    }

    /**
     * @return InvoiceReservationResponse
     * @throws \Exception|Exceptions\ClientException|GuzzleException|ResponseHeaderException|ResponseMessageException
     */
    protected function doResponse()
    {
        $this->doConfigureOptions();
        $headers           = $this->getBasicHeaders();
        $requestParameters = [$this->getHttpMethod(), $this->parseUrl(), $headers];
        if (mb_strtolower($this->getHttpMethod()) === 'post') {
            $requestParameters[] = $this->getPostOptions();
        }
        $request       = new Request(...$requestParameters);
        $this->request = $request;
        try {
            $response       = $this->getClient()->send($request);
            $this->response = $response;

            $output = $this->handleResponse($request, $response);
            $this->validateResponse($output);

            return $output;
        } catch (GuzzleHttpClientException $e) {
            throw new Exceptions\ClientException($e->getMessage(), $e->getRequest(), $e->getResponse(), $e);
        }
    }

    /**
     * @return string
     */
    protected function getPostOptions()
    {
        $options = $this->options;

        return http_build_query($options, '', '&');
    }
}
