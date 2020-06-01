<?php
namespace Mitoop\Silent;

use Closure;
use Log;
use Throwable;

class SilentTry
{
    protected $exceptionCallback; // 发生异常时执行的回调
    protected $finalCallback; // finally 回调
    protected $logable = true; // 是否记录日志 默认记录
    protected $exceptionMsg = ''; // 业务异常信息
    protected $exceptionData = []; // 业务异常数据
    protected $exceptionContext = []; // 异常上下文信息与 $exceptionData 不同的是这个是放在 context 数组, $exceptionData 会拼接到异常信息里
    protected $execContext = []; // 执行的上下文变量 用来从 exec 传递到 exceptionCallback
    protected $logLevel = 'error'; // // 日志错误级别

    public function simpleExec(Closure $exec, $fallbackReturn = false)
    {
        return $this->setLogable(false)->exec($exec, $fallbackReturn);
    }

    public function exec(Closure $exec, $fallbackReturn = false)
    {
        try {
            return $exec($this);
        } catch (Throwable $t) {
            if ($this->logable) {
                $this->log($t, $this->exceptionMsg, $this->exceptionData, $this->exceptionContext);
            }

            if ($exceptionCallback = $this->exceptionCallback) {
                try {
                    $exceptionCallback($this, $t);
                } catch (Throwable $tt) {
                    if ($this->logable) {
                        $this->log($tt, 'FailCallback Error');
                    }
                }
            }
        } finally {
            if ($finalCallback = $this->finalCallback) {
                try {
                    $finalCallback();
                } catch (Throwable $t) {
                    if ($this->logable) {
                        $this->log($t, 'FinalCallback Error');
                    }
                }
            }
        }

        return $fallbackReturn;
    }

    public function setExceptionCallback(Closure $exceptionCallback): SilentTry
    {
        $this->exceptionCallback = $exceptionCallback;

        return $this;
    }

    public function setFinalCallback(Closure $finalCallback): SilentTry
    {
        $this->finalCallback = $finalCallback;

        return $this;
    }

    public function setLogable(bool $logable): SilentTry
    {
        $this->logable = $logable;

        return $this;
    }

    public function setExceptionMsg(string $exceptionMsg): SilentTry
    {
        $this->exceptionMsg = $exceptionMsg;

        return $this;
    }

    public function setExceptionData(array $exceptionData): SilentTry
    {
        $this->exceptionData = $exceptionData;

        return $this;
    }

    public function setExceptionContext(array $exceptionContext): SilentTry
    {
        $this->exceptionContext = $exceptionContext;

        return $this;
    }

    public function setExecContext(array $execContext): SilentTry
    {
        $this->execContext = $execContext;

        return $this;
    }

    public function setLogLevel(string $logLevel): SilentTry
    {
        // @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
        // @see \Illuminate\Log\Logger
        $logLevels = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];

        if (in_array($logLevel, $logLevels)) {
            $this->logLevel = $logLevel;
        }

        return $this;
    }

    protected function log(Throwable $t, string $exceptionMsg, array $exceptionData = [], array $exceptionContext = []): void
    {
        $logLevel = $this->logLevel;

        $message = '';

        if ($exceptionMsg) {
            $message .= "[custom_msg:{$exceptionMsg}]";
            $message .= ' ';
        }

        $message .= "[msg:{$t->getMessage()}]";
        $message .= ' ';
        $message .= "[file:{$t->getFile()}:{$t->getLine()}]";

        if (! empty($exceptionData)) {
            foreach ($exceptionData as $key => $data) {
                $message .= ' ';
                $message .= "[{$key}:{$data}]";
            }
        }

        Log::$logLevel($message, ['context' => $exceptionContext]);
    }
}
