import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { WorkspaceCreatePage } from './workspace-create.page';

describe('WorkspaceCreatePage', () => {
  let component: WorkspaceCreatePage;
  let fixture: ComponentFixture<WorkspaceCreatePage>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [WorkspaceCreatePage],
      providers: [provideHttpClient(), provideHttpClientTesting(), provideRouter([])],
    }).compileComponents();

    fixture = TestBed.createComponent(WorkspaceCreatePage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
