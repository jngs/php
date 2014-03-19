## ConsumErr PHP
What is ComposErr...

### 1. Instalation
...


### 2. Requirements
...

### 3. Writing your own access handler
If you couldn't find any provided handler for programming language of your choice, feel free to write your own.

#### 3.1 Data structure to send to our API service
Our API service expects POST request with JSON formatted data.
The structure is visually represented in the picture below.
![ClassDiagram](https://github.com/consumerr/php/raw/master/docs/en/ConsumerrClassDiagram.png)

**Access**

   * name: label of an access (eg. controller)
   * time: page loading time
   * memory: memory usage
   * url: URI of the request
   * backgroundJob: true should be user if the request is not called by user
   * datetime: unix timestamp when the request occurred
   * errors: array of Error structure
   * events: array of Event structure
   * parts: array of Part structure

**Error (optional)**

   * action: name of an exception
   * message: message of an exception
   * code: exception's code
   * file: path to a file where exception occured
   * line: line of an exception
   * trace: exception's trace as an string
   * severity: exception's severity

**Event (optional)**

   * action: name of an event
   * label: label of an event
   * value: value of an event
   * category: event's category
   * date: unix timestamp when event occured

**Part (optional)**

   * name: name of a part
   * time: time consumed by this part


#### Request example

**Add to headers your account information**

   * X-Consumerr-id
   * X-Consumerr-secret

**Body of a request**

```
{
   "datetime":1392050397,
   "name":"Acme\\DemoBundle\\Controller\\WelcomeController:indexAction",
   "time":0.03391695022583,
   "memory":2271640,
   "url":"http:\/\/www.example.com\/welcome",
   "backgroundJob":false,
   "errors":[
      {
         "exception":"ErrorException",
         "message":"Call to undefined method Acme\\DemoBundle\\Controller\\WelcomeController::FatalExample()",
         "code":500,
         "file":"\/var\/www\/dev\/consumerr\/symfony\/htdocs\/src\/Acme\/DemoBundle\/Controller\/WelcomeController.php",
         "line":16,
         "trace":"",
         "severity":1
      }
   ],
   "events":[
      {
         "action":"Homepage:subscribe",
         "label":"Subscription via homepage form",
         "value":"jaromir.navara@goodshape.cz",
         "category":"subscription",
         "datetime":1392050397
      }
   ],
   "parts":[
      {
         "name":"Core load time",
         "time":0.00816161616156
      },
      {
         "name":"Libraries load time",
         "time":0.01354611548547
      },
      {
         "name":"Templates load time",
         "time":0.02515661848621
      }
   ]
}
```
