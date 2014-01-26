<?php

class MyPreviewException extends Exception {
	function __toString() {
		return "<div style=\"border: 1px solid black;\"><span style=\"font-weight: bold;\">MyPreview Exception:</span><br>
		<p>{$this->getMessage()};<br>
		In {$this->getFile()}, line {$this->getLine()}</p>
		<pre>
		{$this->getTraceAsString()};
		</pre>
		</div>";
	}
}