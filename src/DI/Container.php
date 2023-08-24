<?php

namespace App\DI;

use App\Command\DeleteCommand;
use App\Read\JobsCZReader;
use App\Read\StartupJobsReader;
use App\Database;
use App\DataRenderer;
use App\Template\ColorMode;
use App\Write\DataWriter;
use App\Notification\DiscordJobsNotifier;
use App\Read\PraceCZReader;
use App\Notification\NullJobsNotifier;
use App\Read\ReaderChain;
use App\WebApp;
use App\Command\ReadCommand;
use App\Notification\JobsNotifierInterface;
use App\Read\ReaderInterface;
use App\Template\TemplateFactoryInterface;
use RuntimeException;
use Symfony\Component\Console\Application;
use App\Template\SmartyTemplateFactory;

//tato třída je dependency injector
//zde se registrují všechny možné služby

class Container {
    private array $parameters;

    private array $services = [];

    public function __construct(array $parameters = []) {
        $this->parameters = $parameters;
    }

    public function getWebApp(): WebApp {
        if (isset($this->services['web_app'])) {
            return $this->services['web_app'];
        }

        return $this->services['web_app'] = new WebApp(
            $this->getRenderer()
        );
    }

    public function getConnection(): Database {
        if (isset($this->services['connection'])) {
            return $this->services['connection'];
        }

        return $this->services['connection'] = new Database(
            $this->getParam('db.host'),
            $this->getParam('db.user'),
            $this->getParam('db.dbname'),
            $this->getParam('db.password'),
        );
    }
    public function getTemplateFactory(): TemplateFactoryInterface{
        if (isset($this->services['template_factory'])) {
            return $this->services['template_factory'];
        }

        return $this->services['template_factory'] = new SmartyTemplateFactory();
    }

    public function getJobsNotifier() : JobsNotifierInterface {
        if (isset($this->services['jobs_notifier'])) {
            return $this->services['jobs_notifier'];
        }
        $webhookUrl = $this->getParam("discordWebhookUrl");
        return $this->services['jobs_notifier'] = !empty($webhookUrl) ? new DiscordJobsNotifier($webhookUrl) : new NullJobsNotifier();
    }

    public function getWriter(): DataWriter {
        if (isset($this->services['writer'])) {
            return $this->services['writer'];
        }

        return $this->services['writer'] = new DataWriter(
            $this->getConnection(),
            $this->getJobsNotifier(),
        );
    }

    public function getColorMode(): ColorMode {
        if (isset($this->services['color_mode'])) {
            return $this->services['color_mode'];
        }

        return $this->services['color_mode'] = new ColorMode();
    }

    public function getCommand(): ReadCommand {
        if (isset($this->services['command'])) {
            return $this->services['command'];
        }

        return $this->services['command'] = new ReadCommand($this->getReader(), $this->getWriter(), $this->getConnection());
    }

    public function getDeleteCommand(): DeleteCommand {
        if (isset($this->services['delete_command'])) {
            return $this->services['delete_command'];
        }

        return $this->services['delete_command'] = new DeleteCommand($this->getConnection());
    }

    public function getRenderer(): DataRenderer {
        if (isset($this->services['renderer'])) {
            return $this->services['renderrer'];
        }

        return $this->services['renderer'] = new DataRenderer($this->getConnection(), $this->getTemplateFactory(), $this->getColorMode());
    }


    public function getReader(): ReaderInterface {
        if (isset($this->services['reader'])) {
            return $this->services['reader'];
        }

        return $this->services['reader'] = new ReaderChain([
            $this->getJobsCZReader(),
            $this->getStartupJobsReader(),
        ]);
    }

    private function getJobsCZReader(): JobsCZReader {
        if (isset($this->services['reader.jobscz'])) {
            return $this->services['reader.jobscz'];
        }

        return $this->services['reader.jobscz'] = new JobsCZReader($this->getConnection());
    }

    private function getStartupJobsReader(): StartupJobsReader {
        if (isset($this->services['reader.startupjobs'])) {
            return $this->services['reader.startupjobs'];
        }

        return $this->services['reader.startupjobs'] = new StartupJobsReader($this->getConnection());
    }

    private function getPraceCZReader(): PraceCZReader {
        if (isset($this->services['reader.praceCZ'])) {
            return $this->services['reader.praceCZ'];
        }

        return $this->services['reader.praceCZ'] = new PraceCZReader($this->getConnection());
    }


    private function getParam(string $name) {
        if (!array_key_exists($name, $this->parameters)) {
            throw new RuntimeException(sprintf(
                'Param %s not found.',
                $name
            ));
        }

        return $this->parameters[$name];
    }

    public function getConsoleApplication(): Application {
        if (isset($this->services['console_application'])) {
            return $this->services['console_application'];
        }

        $application = $this->services['console_application'] = new Application();
        $application->add($this->getCommand());
        $application->add($this->getDeleteCommand());
        return $application;
    }
}


