<?php
/**
 * Created by PhpStorm.
 * User: zvinger
 * Date: 25.10.17
 * Time: 14:24
 */

namespace Zvinger\Telegram\console\command;

use Zvinger\Telegram\command\HelpCommand;
use yii\console\Controller;
use yii\helpers\Console;
use Zvinger\Telegram\components\TelegramComponent;

class TelegramConsoleController extends Controller
{
    /**
     * @var TelegramComponent
     */
    private $_telegramComponent;

    /**
     * @param int $timeout
     * @throws \Zvinger\Telegram\exceptions\component\NoTokenProvidedException
     */
    public function actionLongPolling($timeout = 30)
    {
        $telegramComponent = $this->getTelegramComponent();
        $telegramClient = $this->getTelegramComponent()->getTelegramClient();
        Console::output("Started long polling listener");
        while (TRUE) {
            $telegramLastUpdate = $telegramComponent->getLastUpdateId();
            $params = [
                'offset'  => ++$telegramLastUpdate,
                'timeout' => $timeout,
            ];
            $updates = $telegramClient->getUpdates($params);
            foreach ($updates as $update) {
                Console::stdout("Handling update #" . $update->getUpdateId() . PHP_EOL);
                try {
                    $telegramComponent->getIncomingMessageHandler()->workLongPollingUpdate($update);
                } catch (\Exception $e) {

                }
                $telegramComponent->setLastUpdateId($update->getUpdateId());
            }
        }
    }

    public function actionHandleCommands()
    {
        $api = $this->getTelegramComponent()->getTelegramClient();
        $api->addCommands($this->getTelegramComponent()->commands);
        while (TRUE) {
            $api->commandsHandler(FALSE, 30);
        }
    }

    public function actionMe()
    {
        print_r($this->getTelegramComponent()->getBotInfo());
    }

    /**
     * @throws \Zvinger\Telegram\exceptions\message\EmptyChatIdException
     * @throws \Zvinger\Telegram\exceptions\message\EmptyMessageTextException
     * @throws \Zvinger\Telegram\exceptions\component\NoTokenProvidedException
     */
    public function actionMessage($receiver, $message)
    {
        $this->getTelegramComponent()->createMessageHandler($receiver, $message)->send();
    }

    /**
     * @return TelegramComponent
     */
    private function getTelegramComponent(): TelegramComponent
    {
        return $this->_telegramComponent;
    }

    /**
     * @param TelegramComponent $telegramComponent
     */
    public function setTelegramComponent(TelegramComponent $telegramComponent)
    {
        $this->_telegramComponent = $telegramComponent;
    }


}