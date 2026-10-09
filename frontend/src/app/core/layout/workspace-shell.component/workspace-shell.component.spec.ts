import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { WorkspaceShellComponent } from './workspace-shell.component';

describe('WorkspaceShellComponent', () => {
  let component: WorkspaceShellComponent;
  let fixture: ComponentFixture<WorkspaceShellComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [WorkspaceShellComponent],
      providers: [provideHttpClient(), provideHttpClientTesting(), provideRouter([])],
    }).compileComponents();

    fixture = TestBed.createComponent(WorkspaceShellComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('always includes the fixed nav items', () => {
    const labels = component.navItems().map((item) => item.label);
    expect(labels).toEqual(['Dashboard', 'Ingresos', 'Gastos', 'Servicios', 'Cuotas', 'Cierres', 'Sugerencias']);
  });
});
