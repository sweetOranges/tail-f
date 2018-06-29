<?php
/**
 * @author sweetorange
 * 提供了类似于tail -f 的功能， 不同的是支持把一个目录下的文件按照顺序读取
 * 并支持最后一个文件的tail -f
 */
class Tailf
{
  // 当前文件名称
  private $current;
  // 当前文件描述符
  private $fd = 0;
  // 用于匹配文件的正则表达式
  private $pattern = '';
  // 当前文件的偏移量
  private $offset = 0;
  // 运行时文件
  private $runtimeFile = '';

  public function __construct($pattern, $runtimeFile = '')
  {
    $this->pattern = $pattern;
    if (empty($runtimeFile)) {
      return;
    }
    if (!file_exists($runtimeFile)) {
      touch($runtimeFile);
    }
    $this->runtimeFile = $runtimeFile;
    $this->load();

  }
  // 加载运行时文件
  protected function load()
  {
    $data = json_decode(file_get_contents($this->runtimeFile), true);
    if (empty($data)) {
      return false;
    }
    if (isset($data['current'])) {
      $this->current = $data['current'];
    }
    if (isset($data['offset'])) {
      $this->offset = $data['offset'];
    }
    return true;
  }
  // 同步运行时文件
  protected function sync()
  {
    if (empty($this->runtimeFile)) {
      return false;
    }

    file_put_contents($this->runtimeFile, json_encode(['current' => $this->current, 'offset' => $this->offset]));
    return true;
  }
  // 自动轮换文件
  protected function tick()
  {
    $next = $this->next();
    if (empty($this->current)) {
      $this->current = $next;
      $this->offset  = 0;
    }
    if (!empty($this->fd)) {
      fclose($this->fd);
    }
    if (!file_exists($this->current)) {
      throw new Exception($this->current . " is not exits");
    }
    $offset = 0;
    if ($this->current === $next) {
      sleep(1);
      $offset = $this->offset;
    } else {
      $this->current = $next;
      $this->offset  = 0;
    }
    $fd = fopen($this->current, 'r');
    if (empty($fd)) {
      throw new Exception($this->current . " can not open");
    }
    $this->fd = $fd;
    yield from$this->read($this->offset);
  }
  // 读取一个文件
  public function read()
  {
    fseek($this->fd, $this->offset);
    while (!feof($this->fd)) {
      $raw = fgets($this->fd);
      if (!empty($raw)) {
        yield $raw;
      }
      $this->offset = ftell($this->fd);
      $this->sync();
    }
  }
  // 返回迭代器
  public function iterator()
  {
    while (true) {
      foreach ($this->tick() as $raw) {
        yield $raw;
      }
    }
  }
  // 返回下一个文件名
  public function next()
  {
    $queue = glob($this->pattern);
    if (empty($queue)) {
      return false;
    }
    if (empty($this->current)) {
      return $queue[0];
    }
    $index  = array_search($this->current, $queue);
    $length = count($queue) - 1;
    return $index === $length ? $queue[$length] : $queue[$index + 1];
  }
}
