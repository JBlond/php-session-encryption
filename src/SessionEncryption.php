<?php

namespace jblond\session;

use RuntimeException;
use SessionHandlerInterface as SessionHandlerInterfaceAlias;

class SessionEncryption implements SessionHandlerInterfaceAlias
{
    /**
     * @var string
     */
    private string $key;

    /**
     * @var string
     */
    private string $save_path;

    /**
     * session_encrypt constructor.
     * @param string $key
     */
    public function __construct(string $key)
    {
        $this->key = $key;
    }

    /**
     * @return bool
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * @param string $id
     * @return bool
     */
    public function destroy($id): bool
    {
        $file = $this->save_path . '/sess_' . $id;
        if(file_exists($file)){
            unlink($file);
        }
        return true;
    }

    /**
     * @param int $max_lifetime
     * @return bool
     */
    public function gc($max_lifetime): bool
    {
        $files = glob("$this->save_path/sess_*");
        foreach ($files as $file){
            if(file_exists($file) && filemtime($file) + $max_lifetime < time()){
                unlink($file);
            }
        }
        return true;
    }

    /**
     * @param string $path
     * @param string $name
     * @return bool
     * @throws RuntimeException
     */
    public function open($path, $name): bool
    {
        if($path === ''){
            $path = './tmp';
        }
        $this->save_path = $path;
        if(!is_dir($this->save_path) && !mkdir($concurrentDirectory = $this->save_path) && !is_dir($concurrentDirectory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
        session_name($name);
        return true;
    }

    /**
     * @param string $id
     * @return string
     */
    public function read($id): string
    {
        if(!file_exists($this->save_path . '/sess_' . $id)){
            file_put_contents($this->save_path . '/sess_' . $id, '');
        }
        $data = file_get_contents($this->save_path . '/sess_' . $id);
        return $this->decrypt($data, $this->key);
    }

    /**
     * @param string $id
     * @param string $data
     * @return bool
     */
    public function write($id, $data): bool
    {
        $sessionData = $this->encrypt($data, $this->key);
        return !(file_put_contents($this->save_path . '/sess_' . $id, $sessionData) === false);
    }

    /**
     * decrypt()
     *
     * @param string $string
     * @param string $key
     * @return string $return
     */
    private function decrypt(string $string, string $key): string
    {
        $result = '';
        $string = base64_decode($string);
        $length = strlen($string);

        for($i = 0; $i < $length; $i++) {
            $char = $string[$i];
            $keyChar = $key[($i % strlen($key)) - 1];
            $char = chr(ord($char) - ord($keyChar));
            $result .= $char;
        }
        return $result;
    }

    /**
     * encrypt()
     *
     * @param string $string
     * @param string $key
     * @return string $return
     */
    private function encrypt(string $string, string $key): string
    {
        $result = '';
        $length = strlen($string);
        for($i = 0; $i < $length; $i++) {
            $char = $string[$i];
            $keyChar = $key[($i % strlen($key)) - 1];
            $char = chr(ord($char) + ord($keyChar));
            $result .= $char;
        }

        return base64_encode($result);
    }
}
