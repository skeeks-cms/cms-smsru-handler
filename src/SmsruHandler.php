<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\sms\smsru;

use skeeks\cms\models\CmsSmsMessage;
use skeeks\cms\sms\SmsHandler;
use skeeks\yii2\form\fields\FieldSet;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\httpclient\Client;

/**
 *
 * @see https://smsimple.ru/api-http/
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class SmsruHandler extends SmsHandler
{
    public $api_key = "";
    public $sender = "";

    /**
     * @return array
     */
    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name' => \Yii::t('skeeks/shop/app', 'sms.ru'),
        ]);
    }


    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['api_key'], 'required'],
            [['api_key'], 'string'],
            [['sender'], 'string'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'api_key' => "API ключ",
            'sender'  => "Отправитель",

        ]);
    }

    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [

        ]);
    }


    /**
     * @return array
     */
    public function getConfigFormFields()
    {
        return [
            'main' => [
                'class'  => FieldSet::class,
                'name'   => 'Основные',
                'fields' => [
                    'api_key',
                    'sender',
                ],
            ],
        ];
    }


    /**
     * @see https://smsimple.ru/api-http/
     *
     * @param      $phone
     * @param      $text
     * @param null $sender
     * @return $message_id
     */
    public function send($phone, $text, $sender = null)
    {
        $queryString = http_build_query([
            'api_id'    => $this->api_key,
            'to'    => $phone,
            'msg'   => $text,
            'json'   => 1,
        ]);

        $url = 'https://sms.ru/sms/send?'.$queryString;

        $client = new Client();
        $response = $client
            ->createRequest()
            ->setFormat(Client::FORMAT_JSON)
            ->setUrl($url)
            ->send();

        if (!$response->isOk) {
            throw new Exception($response->content);
        }

        return $response->data;
    }

    public function sendMessage(CmsSmsMessage $cmsSmsMessage)
    {
        $data = $this->send($cmsSmsMessage->phone, $cmsSmsMessage->message);
        if (ArrayHelper::getValue($data, 'status') == "ERROR") {
            $cmsSmsMessage->status = CmsSmsMessage::STATUS_ERROR;
            $cmsSmsMessage->provider_status = (string) ArrayHelper::getValue($data, 'status');
            $cmsSmsMessage->error_message = ArrayHelper::getValue($data, 'status_text');
            return;
        }

        $status = ArrayHelper::getValue($data, ['sms', $cmsSmsMessage->phone, 'status']);
        if ($status == "OK") {
            $cmsSmsMessage->status = CmsSmsMessage::STATUS_DELIVERED;
            $cmsSmsMessage->provider_status = (string) ArrayHelper::getValue($data, ['sms', $cmsSmsMessage->phone, 'status_code']);
            $cmsSmsMessage->provider_message_id = ArrayHelper::getValue($data, ['sms', $cmsSmsMessage->phone, 'sms_id']);
        } else {
            $cmsSmsMessage->status = CmsSmsMessage::STATUS_ERROR;
            $cmsSmsMessage->provider_status = (string) ArrayHelper::getValue($data, ['sms', $cmsSmsMessage->phone, 'status_code']);
            $cmsSmsMessage->error_message = ArrayHelper::getValue($data, ['sms', $cmsSmsMessage->phone, 'status_text']);
        }
    }

    /**
     * @param $message_id
     * @return mixed
     */
    public function status($message_id)
    {

    }
}