# silverstripe-userdefinedforms-payments

User Defined Forms Conditional Payments
Let's you add conditions to calculate amount and require payment using omnipay module 

+ Install it using composer
`composer require a2nt/userdefinedforms-payments`

+ Define Payment configuration
app/_config/api-payment.yml

```
---
Name: 'webapp-api-payment'
---
SilverStripe\Omnipay\Model\Payment:
  allowed_gateways:
    - 'PayPal_Express'

SilverStripe\Omnipay\GatewayInfo:
  PayPal_Express:
    parameters:
      username: ''
      password: ''
      signature: ''
      testMode: true # Make sure to override this to false
```

![Screenshot from 2021-07-07 00-04-03](https://user-images.githubusercontent.com/672794/124674513-2ec75c80-debb-11eb-86c6-28fc4733ef1e.png)
