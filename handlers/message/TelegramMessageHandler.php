<?php
/**
 * Created by PhpStorm.
 * User: zvinger
 * Date: 24.10.17
 * Time: 15:03
 */

namespace Zvinger\Telegram\handlers\message;

use Telegram\Bot\Exceptions\TelegramResponseException;
use yii\helpers\ArrayHelper;
use Zvinger\Telegram\components\TelegramComponent;
use Zvinger\Telegram\exceptions\message\EmptyChatIdException;
use Zvinger\Telegram\exceptions\message\EmptyMessageTextException;

class TelegramMessageHandler
{
    const PARSE_HTML = 'HTML';
    const PARSE_MARKDOWN = 'Markdown';

    private $_text;

    private $_receiver_chat_id;

    private $_parse_mode = self::PARSE_HTML;

    private $_background = true;

    private $_telegram_component;

    private $_message_id_to_edit;

    private $_reply_markup = null;

    /**
     * TelegramMessageHandler constructor.
     * @param $telegramComponent
     * @param $_receiver_chat_id
     * @param $_text
     */
    public function __construct(TelegramComponent $telegramComponent, $_receiver_chat_id = null, $_text = null)
    {
        $this->_text = $_text;
        $this->_receiver_chat_id = $_receiver_chat_id;
        $this->_telegram_component = $telegramComponent;
    }

    /**
     * @param mixed $text
     * @return $this
     */
    public function setText($text)
    {
        $this->_text = $text;

        return $this;
    }

    /**
     * @param mixed $_receiver_chat_id
     * @return $this
     */
    public function setReceiverChatId($_receiver_chat_id)
    {
        $this->_receiver_chat_id = $_receiver_chat_id;

        return $this;
    }

    /**
     * @return \Telegram\Bot\Objects\Message
     * @throws EmptyChatIdException
     * @throws EmptyMessageTextException
     * @throws \Zvinger\Telegram\exceptions\component\NoTokenProvidedException
     */
    public function send()
    {

        if (empty($this->_text)) {
            throw new EmptyMessageTextException();
        }

        //  todo сделать получение результата отправки сообщения у фоновых отправок сообщений

        if (empty($this->_message_id_to_edit)) {
            return $this->sendMessage();
        } else {
            return $this->editMessageText($this->_message_id_to_edit);
        }
    }

    private function getChatId()
    {
        if (empty($this->_receiver_chat_id)) {
            throw new EmptyChatIdException();
        }
        $receiver_chat_id = $this->_receiver_chat_id;
        $id = ArrayHelper::getValue($this->_telegram_component->namedContacts, $receiver_chat_id, $receiver_chat_id);
        if (empty($id)) {
            $id = $receiver_chat_id;
        }

        return $id;
    }

    /**
     * @return \Telegram\Bot\Objects\Message
     * @throws EmptyChatIdException
     * @throws \Zvinger\Telegram\exceptions\component\NoTokenProvidedException
     */
    private function sendMessage(): \Telegram\Bot\Objects\Message
    {
        $params = $this->getBaseParams();

        return $this->_telegram_component->getTelegramClient()->sendMessage($params);
    }

    /**
     * @param $messageId
     * @return mixed
     * @throws EmptyChatIdException
     * @throws \Zvinger\Telegram\exceptions\component\NoTokenProvidedException
     */
    public function editMessageText($messageId)
    {
        $params = $this->getBaseParams();
        $params['message_id'] = $messageId;

        return $this->_telegram_component->getTelegramClient()->editMessageText($params);
    }

    public function foreground()
    {
        $this->_background = false;

        return $this;
    }

    public function background()
    {
        $this->_background = true;

        return $this;
    }

    /**
     * @param string $parse_mode
     * @return TelegramMessageHandler
     */
    public function setParseMode(string $parse_mode): TelegramMessageHandler
    {
        $this->_parse_mode = $parse_mode;

        return $this;
    }

    /**
     * @param mixed $message_id_to_edit
     * @return $this
     */
    public function setMessageIdToEdit($message_id_to_edit)
    {
        $this->_message_id_to_edit = $message_id_to_edit;

        return $this;
    }

    public function setKeyboardMarkup($markup)
    {
        $this->_reply_markup = $markup;

        return $this;
    }

    /**
     * @return null
     */
    public function getText()
    {
        return $this->_text;
    }

    /**
     * @return array
     * @throws EmptyChatIdException
     */
    private function getBaseParams(): array
    {
        $params = [
            'chat_id' => $this->getChatId(),
            'text' => $this->_text,
            'parse_mode' => $this->_parse_mode,
        ];
        if ($this->_reply_markup !== null) {
            $params['reply_markup'] = json_encode($this->_reply_markup);
        }

        return $params;
    }
}
