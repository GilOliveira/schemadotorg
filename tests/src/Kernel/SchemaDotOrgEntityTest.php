<?php

namespace Drupal\Tests\schemadotorg\Kernel;

/**
 * Base class to testing entity type/bundle that are mapped to Schema.org types.
 *
 * @group schemadotorg
 */
class SchemaDotOrgEntityTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Tests creating common entity type/bundle Schema.org types.
   *
   * Schema.org types includes...
   * - paragraph:ContentPoint
   * - media:ImageObject
   * - user:Person
   * - node:Place
   * - node:Organization
   * - node:Event.
   */
  public function testCreateSchemaEntity() {
    // Check creating paragraph:ContentPoint Schema.org mapping.
    $mapping = $this->createSchemaEntity('paragraph', 'ContactPoint');
    $this->assertEquals('paragraph', $mapping->getTargetEntityTypeId());
    $this->assertEquals('contact_point', $mapping->getTargetBundle());
    $this->assertEquals('ContactPoint', $mapping->getSchemaType());
    $this->assertFalse($mapping->supportsSubtyping());
    $this->assertEquals($mapping->getSchemaProperties(), [
      'schema_contact_type' => 'contactType',
      'schema_email' => 'email',
      'schema_telephone' => 'telephone',
    ]);

    // Check creating media:ImageObject Schema.org mapping.
    $this->createMediaImage();
    $mapping = $this->createSchemaEntity('media', 'ImageObject');
    $this->assertEquals('media', $mapping->getTargetEntityTypeId());
    $this->assertEquals('image', $mapping->getTargetBundle());
    $this->assertEquals('ImageObject', $mapping->getSchemaType());
    $this->assertFalse($mapping->supportsSubtyping());
    $this->assertEquals($mapping->getSchemaProperties(), [
      'created' => 'dateCreated',
      'changed' => 'dateModified',
      'field_media_image' => 'image',
      'langcode' => 'inLanguage',
      'name' => 'name',
      'thumbnail' => 'thumbnail',
    ]);

    // Check creating user:Person Schema.org mapping.
    $mapping = $this->createSchemaEntity('user', 'Person');
    $this->assertEquals('user', $mapping->getTargetEntityTypeId());
    $this->assertEquals('user', $mapping->getTargetBundle());
    $this->assertEquals('Person', $mapping->getSchemaType());
    $this->assertFalse($mapping->supportsSubtyping());
    $this->assertEquals($mapping->getSchemaProperties(), [
      'mail' => 'email',
      'name' => 'name',
      'schema_additional_name' => 'additionalName',
      'schema_address' => 'address',
      'schema_affiliation' => 'affiliation',
      'schema_alumni_of' => 'alumniOf',
      'schema_award' => 'award',
      'schema_birth_date' => 'birthDate',
      'schema_contact_point' => 'contactPoint',
      'schema_description' => 'description',
      'schema_family_name' => 'familyName',
      'schema_gender' => 'gender',
      'schema_given_name' => 'givenName',
      'schema_honorific_prefix' => 'honorificPrefix',
      'schema_honorific_suffix' => 'honorificSuffix',
      'schema_job_title' => 'jobTitle',
      'schema_knows_lang' => 'knowsLanguage',
      'schema_nationality' => 'nationality',
      'schema_same_as' => 'sameAs',
      'schema_telephone' => 'telephone',
      'schema_works_for' => 'worksFor',
      'user_picture' => 'image',
    ]);

    // Check creating node:Place Schema.org mapping.
    $mapping = $this->createSchemaEntity('node', 'Place');
    $this->assertEquals('node', $mapping->getTargetEntityTypeId());
    $this->assertEquals('place', $mapping->getTargetBundle());
    $this->assertEquals('Place', $mapping->getSchemaType());
    $this->assertFalse($mapping->supportsSubtyping());
    $this->assertEquals($mapping->getSchemaProperties(), [
      'body' => 'description',
      'schema_address' => 'address',
      'schema_image' => 'image',
      'schema_telephone' => 'telephone',
      'title' => 'name',
    ]);

    // Check creating node:Organization Schema.org mapping.
    $mapping = $this->createSchemaEntity('node', 'Organization');
    $this->assertEquals('node', $mapping->getTargetEntityTypeId());
    $this->assertEquals('organization', $mapping->getTargetBundle());
    $this->assertEquals('Organization', $mapping->getSchemaType());
    $this->assertFalse($mapping->supportsSubtyping());
    $this->assertEquals($mapping->getSchemaProperties(), [
      'body' => 'description',
      'schema_address' => 'address',
      'schema_contact_point' => 'contactPoint',
      'schema_email' => 'email',
      'schema_image' => 'image',
      'schema_member_of' => 'memberOf',
      'schema_same_as' => 'sameAs',
      'schema_telephone' => 'telephone',
      'title' => 'name',
    ]);

    // Check creating node:Event Schema.org mapping.
    $mapping = $this->createSchemaEntity('node', 'Event');
    $this->assertEquals('node', $mapping->getTargetEntityTypeId());
    $this->assertEquals('event', $mapping->getTargetBundle());
    $this->assertEquals('Event', $mapping->getSchemaType());
    $this->assertTrue($mapping->supportsSubtyping());
    $this->assertEquals($mapping->getSchemaProperties(), [
      'body' => 'description',
      'langcode' => 'inLanguage',
      'schema_attendee' => 'attendee',
      'schema_audience' => 'audience',
      'schema_duration' => 'duration',
      'schema_end_date' => 'endDate',
      'schema_event_status' => 'eventStatus',
      'schema_image' => 'image',
      'schema_location' => 'location',
      'schema_organizer' => 'organizer',
      'schema_performer' => 'performer',
      'schema_start_date' => 'startDate',
      'title' => 'name',
    ]);
  }

}
