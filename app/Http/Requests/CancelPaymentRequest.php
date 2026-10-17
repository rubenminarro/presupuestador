<?php

namespace App\Http\Requests;

/**
 * Mismas reglas que la anulación de factura: motivo obligatorio.
 */
class CancelPaymentRequest extends CancelInvoiceRequest
{
}
