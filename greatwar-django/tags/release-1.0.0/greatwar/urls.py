from django.conf.urls.defaults import *
from django.conf import settings
from django.conf.urls.defaults import patterns, url

# Uncomment the next two lines to enable the admin:
# from django.contrib import admin
# admin.autodiscover()

urlpatterns = patterns('',
    # Example:
    # (r'^greatwar/', include('greatwar.foo.urls')),
    url(r'^$', 'views.index', name="index"),
    url(r'^about/$', 'views.about', name="about"),
    url(r'^links/$', 'views.links', name="links"),
    url(r'^credits/$', 'views.credits', name="credits"),
    url(r'^poetry/', include('greatwar.poetry.urls', namespace='poetry')),
    url(r'^postcards/', include('greatwar.postcards.urls', namespace='postcards')),
    # Uncomment the admin/doc line below and add 'django.contrib.admindocs' 
    # to INSTALLED_APPS to enable admin documentation:
    # (r'^admin/doc/', include('django.contrib.admindocs.urls')),

    # Uncomment the next line to enable the admin:
    # (r'^admin/', include(admin.site.urls)),
          
)

# DISABLE THIS IN PRODUCTION
if settings.DEV_ENV:

    urlpatterns += patterns('',
        (r'^static/(?P<path>.*)$', 'django.views.static.serve', {
            'document_root': settings.MEDIA_ROOT,
            }),
    )

