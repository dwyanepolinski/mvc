{{ extends base/frame#content }}

{{ block content }}
<nav class="navbar navbar-expand-lg navbar-light bg-light">
	<a class="navbar-brand" href="#">{{ project_name }}</a>
	<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
		<span class="navbar-toggler-icon"></span>
	</button>
	<div class="collapse navbar-collapse" id="navbarNavAltMarkup">
		<div class="navbar-nav">
			<a class="nav-item nav-link active" href="#">Home <span class="sr-only">(current)</span></a>
			<a class="nav-item nav-link" href="#">Features</a>
			<a class="nav-item nav-link" href="#">Pricing</a>
			<a class="nav-item nav-link disabled" href="#">Disabled</a>
		</div>
	</div>
</nav>

{% if project_name == 1 %}
	<p>if spełniony</p>
	<p>druga linia</p>
{% elif project_name == 2 %}
	<p>elif spełniony</p>
	{% if var %}
		<p>{{ var }}</p>
	{% else %}
		<p>Variable var not set</p>
	{% endif %}
	<p>po ifie z varem</p>
{% else %}
	<p>else spełniony</p>
{% endif %}

{% for i in list %}
	<p>Element {{ i }}</p>
	<p>{{ forloop.counter }}</p>
	{% for j in xlist %}
		{{ forloop.counter }}
	{% endfor %}
	{{ forloop.counter }}
	<p><b>{{ forloop.counter0 }}</b></p>
	<p>Element {{ i }}</p>
	<p>{{ forloop.counter }}</p>
	{% for j in xlist %}
		{{ forloop.counter }}
	{% endfor %}
{% endfor %}


<br>
<br>
<br>

{% if ok %}
	hehe
{% endif %}

<div class="container">
	<div class="jumbotron">
		<h1 class="display-4">Hello!</h1>
		<p class="lead">Szablon bazowy bootstrap.</p>
		<hr class="my-4">
		<p>Strona główna do projektu, szablon bazowy bootstrap 4.</p>
	</div>
</div>
{{ endblock }}